<?php

declare(strict_types=1);

namespace Phluxor\WebSocket\Middleware;

use Phluxor\WebSocket\Constant;
use Phluxor\WebSocket\Exception\WebSocketException;
use Phluxor\WebSocket\Exception\InvokeException;
use Phluxor\WebSocket\Exception\NotFoundException;
use Phluxor\WebSocket\MessageInterface;
use Phluxor\WebSocket\Request;
use Phluxor\WebSocket\RequestHandlerInterface;
use Phluxor\WebSocket\Response;
use Phluxor\WebSocket\Status;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class ServiceHandler implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): MessageInterface
    {
        $service = $request->service;
        $method = $request->method;
        $context = $request->getContext();
        try {
            $serverService = $context->getValue(Constant::SERVER_SERVICES);
            if (!is_array($serverService)) {
                throw new NotFoundException("$service::$method not found");
            }
            if (!isset($serverService[$service])) {
                throw new NotFoundException("$service::$method not found");
            }
            $output = $serverService[$service]->handle($request);
        } catch (WebSocketException $e) {
            $this->logger->error(
                $e->getMessage(),
                ['error_code' => $e->getCode(), 'trace' => $e->getTraceAsString()]
            );
            $output = '';
        } catch (\Swoole\Exception $e) {
            $this->logger->warning(
                $e->getMessage(),
                ['error_code' => $e->getCode(), 'trace' => $e->getTraceAsString()]
            );
            $output = '';
        } catch (Throwable $e) {
            throw new InvokeException($e->getMessage(), Status::INTERNAL, $e);
        }
        return new Response($context, $output);
    }
}
