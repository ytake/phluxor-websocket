<?php

declare(strict_types=1);

namespace Test;

use Phluxor\WebSocket\Context;
use Phluxor\WebSocket\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testResponse(): void
    {
        $response = new Response(new Context([]), 'payload');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf(Context::class, $response->context);
        $this->assertSame('payload', $response->payload);
    }
}