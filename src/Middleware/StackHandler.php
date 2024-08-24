<?php

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
