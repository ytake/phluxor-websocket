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
