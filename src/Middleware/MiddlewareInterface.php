<?php

declare(strict_types=1);

namespace Phluxor\WebSocket\Middleware;

use Phluxor\WebSocket\MessageInterface;
use Phluxor\WebSocket\Request;
use Phluxor\WebSocket\RequestHandlerInterface;

interface MiddlewareInterface
{
    /**
     * @param Request $request
     * @param RequestHandlerInterface $handler
     * @return MessageInterface|null
     */
    public function process(
        Request $request,
        RequestHandlerInterface $handler
    ): ?MessageInterface;
}
