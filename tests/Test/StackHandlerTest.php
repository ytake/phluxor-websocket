<?php

declare(strict_types=1);

namespace Test;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Phluxor\WebSocket\Context;
use Phluxor\WebSocket\Middleware\LoggingMiddleware;
use Phluxor\WebSocket\Middleware\StackHandler;
use Phluxor\WebSocket\Middleware\TraceMiddleware;
use Phluxor\WebSocket\Request;
use PHPUnit\Framework\TestCase;

class StackHandlerTest extends TestCase
{
    public function testAdd(): void
    {
        $middleware = new TraceMiddleware();
        $stack = new StackHandler();
        $stack = $stack->add($middleware);
        $this->assertInstanceOf(StackHandler::class, $stack);

        $r = $stack->handle(new Request(new Context([]), 'service', 'method', 'payload'));
        $this->assertNull($r);
    }

    public function testShouldWriteInfoLog(): void
    {
        $log = $this->logger();
        $stack = new StackHandler(
            new LoggingMiddleware($log),
        );
        $r = $stack->handle(
            new Request(new Context([]), 'service', 'method', 'payload')
        );
        $this->assertNull($r);
        $records = $log->getHandlers()[0]->getRecords();
        $this->assertNotCount(0, $records);
        $this->assertEquals('WebSocket request: :->', $records[0]['message']);
    }

    private function logger(): Logger
    {
        $log = new Logger('Phluxor');
        $log->useLoggingLoopDetection(false);
        $log->pushHandler(new TestHandler());
        return $log;
    }
}
