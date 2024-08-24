<?php

declare(strict_types=1);

# Generated by the protocol buffer compiler (for Phluxor). DO NOT EDIT!
# source: protobuf/helloworld.proto

namespace Test\ProtoBuf;

use Phluxor\WebSocket;

interface StreamInterface extends WebSocket\ServiceInterface
{
    public const string NAME = "/helloworld.Stream";

    /**
     * @param WebSocket\ContextInterface $ctx
     * @param HelloRequest $request
     * @return HelloReply
     *
     * @throws WebSocket\Exception\InvokeException
     */
    public function FetchResponse(WebSocket\ContextInterface $ctx, HelloRequest $request): HelloReply; // @phpcs:ignore
}
