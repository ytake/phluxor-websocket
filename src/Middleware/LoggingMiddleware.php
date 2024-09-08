<?php

/**
 * Copyright 2024 Yuuki Takezawa <yuuki.takezawa@comnect.jp.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
        $client = '';
        if (isset($rawRequest->server)) {
            $client = $rawRequest->server['remote_addr'] . ':' . $rawRequest->server['remote_port'];
        }
        $server = '';
        if (isset($rawRequest->header)) {
            $server = $rawRequest->header['host'];
        }
        $this->logger->info(
            "WebSocket request: $client->$server",
            [
                'service' => sprintf("%s/%s", $request->service, $request->method),
            ]
        );
        return $handler->handle($request);
    }
}
