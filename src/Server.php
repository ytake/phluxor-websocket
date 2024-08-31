<?php

declare(strict_types=1);

namespace Phluxor\WebSocket;

use Phluxor\WebSocket\Exception\WebSocketException;
use Phluxor\WebSocket\Exception\InvokeException;
use Phluxor\WebSocket\Middleware\MiddlewareInterface;
use Phluxor\WebSocket\Middleware\ServiceHandler;
use Phluxor\WebSocket\Middleware\StackHandler;
use Psr\Log\LoggerInterface;
use Swoole\Exception;
use Swoole\WebSocket\CloseFrame;
use Throwable;

class Server
{
    /**
     * @psalm-suppress UndefinedClass
     * @var array<string, mixed>
     */
    private array $settings = [
        \Swoole\Constant::OPTION_ENABLE_COROUTINE => true,
        \Swoole\Constant::OPTION_HTTP_COMPRESSION => true,
        \Swoole\Constant::OPTION_HTTP2_MAX_FRAME_SIZE => 2 * 1024 * 1024,
    ];

    /** @var array<string, ServiceContainer> */
    private array $services = [];
    private \Swoole\Coroutine\Http\Server $server;
    private StackHandler $handler;

    public function __construct(
        private readonly LoggerInterface $logger,
        string $host,
        int $port = 0,
    ) {
        $this->server = new \Swoole\Coroutine\Http\Server($host, $port);
        $this->handler = (new StackHandler())->add(new ServiceHandler($this->logger));
    }

    /**
     * @param array<string, mixed> $settings
     * @return $this
     */
    public function withSetting(array $settings): self
    {
        $this->settings = array_merge($this->settings, $settings);
        return $this;
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return $this
     */
    public function withMiddleware(MiddlewareInterface $middleware): self
    {
        $this->handler = $this->handler->add($middleware);
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function registerService(ServiceInterface $instance): self
    {
        $service = new ServiceContainer($instance);
        $this->services[$service->name] = $service;
        return $this;
    }

    public function start(): void
    {
        $this->server->set($this->settings);
        $this->buildHandlers();
        $this->server->start();
    }

    public function stop(): void
    {
        $this->server->shutdown();
    }

    private function buildHandlers(): void
    {
        foreach ($this->services as $serviceName => $container) {
            $this->server->handle(
                $container->name,
                function (\Swoole\Http\Request $request, \Swoole\Http\Response $websocket) {
                    // enable websocket
                    $websocket->upgrade();
                    while (true) {
                        $frame = $websocket->recv();
                        if ($frame === '') {
                            $websocket->close();
                            break;
                        } else {
                            if ($frame === false) {
                                $this->logger->error(
                                    'websocket frame error',
                                    ['error_code' => swoole_last_error()]
                                );
                                $websocket->close();
                                break;
                            } else {
                                if ($frame->data == 'close' || get_class($frame) === CloseFrame::class) {
                                    $websocket->close();
                                    break;
                                }
                                $context = new Context([
                                    Constant::SERVER_WORKER_CONTEXT => new Context([
                                        \Phluxor\WebSocket\Server::class => $this,
                                        \Swoole\Coroutine\HTTP\Server::class => $this->server,
                                    ]),
                                    Constant::SERVER_SERVICES => $this->services,
                                    \Swoole\Http\Request::class => $request,
                                    \Swoole\Http\Response::class => $websocket,
                                ]);
                                try {
                                    [, $service, $method] = explode('/', $request->server['request_uri'] ?? '');
                                    $service = '/' . $service;
                                    $message = $frame->data ? substr($frame->data, 5) : '';
                                    $phluxorRequest = new Request($context, $service, $method, $message);
                                    $response = $this->handler->handle($phluxorRequest);
                                } catch (WebSocketException $e) {
                                    $this->logger->error(
                                        $e->getMessage(),
                                        [
                                            'error_code' => $e->getCode(),
                                            'trace' => $e->getTraceAsString(),
                                            'previous' => $e->getPrevious(),
                                        ]
                                    );
                                    $output = '';
                                    $response = new Response($context, $output);
                                }
                                if ($response != null) {
                                    $this->send($response);
                                }
                            }
                        }
                    }
                }
            );
        }
    }

    private function send(Response $response): void
    {
        $context = $response->context;
        /** @var \Swoole\Http\Response $rawResponse */
        $rawResponse = $context->getValue(\Swoole\Http\Response::class);
        $payload = pack('CN', 0, strlen($response->payload)) . $response->payload;
        $rawResponse->end($payload);
    }

    /**
     * @param Message $message
     * @return bool
     * @throws Exception
     */
    public function push(Message $message): bool
    {
        $context = $message->context;
        try {
            $payload = $message->message->serializeToString();
        } catch (Throwable $e) {
            throw new InvokeException($e->getMessage(), Status::INTERNAL, $e);
        }
        $payload = pack('CN', 0, strlen($payload)) . $payload;
        /** @var \Swoole\Http\Response $response */
        $response = $context->getValue(\Swoole\Http\Response::class);
        $result = $response->push($payload);
        if (!$result) {
            throw new \Swoole\Exception('Client side is disconnected');
        }
        return true;
    }
}
