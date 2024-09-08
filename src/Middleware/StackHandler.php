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

class StackHandler implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private array $middlewares;

    public function __construct(
        MiddlewareInterface ...$middlewares
    ) {
        $this->middlewares = $middlewares;
    }

    public function add(MiddlewareInterface $middleware): self
    {
        $stack = clone $this;
        array_unshift($stack->middlewares, $middleware);
        return $stack;
    }

    public function handle(Request $request): ?MessageInterface
    {
        $middleware = $this->middlewares[0] ?? false;
        if ($middleware === false) {
            return null;
        }
        return $middleware->process($request, $this->withoutMiddleware($middleware));
    }

    private function withoutMiddleware(
        MiddlewareInterface $middleware
    ): RequestHandlerInterface {
        return new self(
            ...array_filter(
                $this->middlewares,
                function ($m) use ($middleware) {
                    return $middleware !== $m;
                }
            )
        );
    }
}
