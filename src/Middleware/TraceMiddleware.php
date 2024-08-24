<?php

declare(strict_types=1);

namespace Phluxor\WebSocket\Middleware;

use Phluxor\WebSocket\MessageInterface;
use Phluxor\WebSocket\Request;
use Phluxor\WebSocket\RequestHandlerInterface;

class TraceMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): ?MessageInterface
    {
        // here we can add some tracing logic
        return $handler->handle($request);
    }
}
