<?php

declare(strict_types=1);

namespace Test;

use Phluxor\WebSocket\Context;
use Phluxor\WebSocket\Request;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Response;

class RequestTest extends TestCase
{
    public function testRequest(): void
    {
        $request = new Request(new Context([]), 'payload', 'hoge', new Response());
        $this->assertInstanceOf(Request::class, $request);
        $this->assertTrue(true);
    }
}