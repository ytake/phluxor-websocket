<?php

declare(strict_types=1);

namespace Phluxor\WebSocket;

use Phluxor\WebSocket\Exception\ConnectionClosedException;
use Phluxor\WebSocket\Exception\WebSocketException;
use Phluxor\WebSocket\Exception\InvokeException;
use Phluxor\WebSocket\Middleware\MiddlewareInterface;
use Phluxor\WebSocket\Middleware\ServiceHandler;
use Phluxor\WebSocket\Middleware\StackHandler;
use Psr\Log\LoggerInterface;
use Swoole\Exception;
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

    /** @var array<string, RequestHandlerInterface> */
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
    public function registerService(string $name, RequestHandlerInterface $service): self
    {
        $this->services[$name] = $service;
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
        foreach ($this->services as $name => $container) {
            $this->server->handle(
                $name,
                function (\Swoole\Http\Request $request, \Swoole\Http\Response $websocket) {
                    // enable websocket
                    $websocket->upgrade();
                    $context = new Context([
                        Constant::SERVER_WORKER_CONTEXT => new Context([
                            \Phluxor\WebSocket\Server::class => $this,
                            \Swoole\Coroutine\HTTP\Server::class => $this->server, // @phpstan-ignore-line
                        ]),
                        Constant::SERVER_SERVICES => $this->services,
                        \Swoole\Http\Request::class => $request,
                        \Swoole\Http\Response::class => $websocket,
                    ]);
                    try {
                        [, $service, $method] = explode('/', $request->server['request_uri'] ?? '');
                        $service = '/' . $service;
                        $phluxorRequest = new Request($context, $service, $method, $websocket);
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
                    } catch (ConnectionClosedException $e) {
                        $this->logger->info($e->getMessage());
                        $output = '';
                        $response = new Response($context, $output);
                    }
                    if ($response != null) {
                        $this->send($response); // @phpstan-ignore-line
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
