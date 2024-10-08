<?php

declare(strict_types=1);

# Generated by the protocol buffer compiler (for Phluxor). DO NOT EDIT!
# source: protobuf/helloworld.proto

namespace Test\ProtoBuf;

use Closure;
use Phluxor\WebSocket;

class GreeterService implements GreeterInterface
{
    private ?Closure $assert = null;

    /**
     * @param WebSocket\ContextInterface $ctx
     * @param WebSocket\Stream $stream
     * @return void
     *
     * @throws WebSocket\Exception\InvokeException|\Exception
     */
    public function SayHello(WebSocket\ContextInterface $ctx, WebSocket\Stream $stream): void // @phpcs:ignore
    {
        $r = $stream->recv();
        $callback = $this->assert;
        if ($callback) {
            $callback($r);
        }
        return;
    }

    /**
     * @param Closure(WebSocket\Message): void $callback
     */
    public function assert(Closure $callback): void
    {
        $this->assert = $callback;
    }
}
