<?php

declare(strict_types=1);

namespace Phluxor\WebSocket\Middleware;

use Phluxor\WebSocket\MessageInterface;
use Phluxor\WebSocket\Request;
use Phluxor\WebSocket\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

readonly class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): ?MessageInterface
    {
        $context = $request->getContext();
        /** @var \Swoole\Http\Request $rawRequest */
        $rawRequest = $context->getValue(\Swoole\Http\Request::class);
        $client = $rawRequest->server['remote_addr'] . ':' . $rawRequest->server['remote_port'];
        $server = $rawRequest->header['host'];
        $streamId = $rawRequest->streamId;
        $this->logger->info(
            "WebSocket request: {$client}->{$server}",
            [
                'stream' => $streamId,
                'service' => sprintf("%s/%s", $request->service, $request->method),
            ]
        );
        return $handler->handle($request);
    }
}
