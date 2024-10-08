<?php

declare(strict_types=1);

# Generated by the protocol buffer compiler (for Phluxor). DO NOT EDIT!
# source: protobuf/helloworld.proto

namespace Test\ProtoBuf;

use Phluxor\WebSocket;

class StreamClient extends WebSocket\BaseStub
{
    /**
     * @param HelloRequest $request
     * @param array<string|int, mixed> $metadata
     * @return ?HelloReply
     *
     * @throws WebSocket\Exception\InvokeException|\Exception
     */
    public function FetchResponse(HelloRequest $request, array $metadata = []): ?HelloReply // @phpcs:ignore
    {
    	return $this->serverRequest(
            '/helloworld.Stream/FetchResponse',
            $request,
            ['\Test\ProtoBuf\HelloReply', 'decode'],
            $metadata
        );
    }
}
