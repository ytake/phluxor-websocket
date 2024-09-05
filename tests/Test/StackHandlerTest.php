<?php

declare(strict_types=1);

namespace Test;

use Phluxor\WebSocket\Middleware\StackHandler;
use Phluxor\WebSocket\Middleware\TraceMiddleware;
use PHPUnit\Framework\TestCase;

class StackHandlerTest extends TestCase
{
    public function testAdd(): void
    {
        $middleware = new TraceMiddleware();
        $stack = new StackHandler();
        $stack = $stack->add($middleware);
        $this->assertInstanceOf(StackHandler::class, $stack);
    }
}
