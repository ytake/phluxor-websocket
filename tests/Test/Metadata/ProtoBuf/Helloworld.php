<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: protobuf/helloworld.proto

namespace Test\Metadata\ProtoBuf;

class Helloworld
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        $pool->internalAddGeneratedFile(
            "\x0A\xB3\x02\x0A\x19protobuf/helloworld.proto\x12\x0Ahelloworld\"\x1C\x0A\x0CHelloRequest\x12\x0C\x0A\x04name\x18\x01 \x01(\x09\"\x1D\x0A\x0AHelloReply\x12\x0F\x0A\x07message\x18\x01 \x01(\x092I\x0A\x07Greeter\x12>\x0A\x08SayHello\x12\x18.helloworld.HelloRequest\x1A\x16.helloworld.HelloReply\"\x002O\x0A\x06Stream\x12E\x0A\x0DFetchResponse\x12\x18.helloworld.HelloRequest\x1A\x16.helloworld.HelloReply\"\x000\x01B)\xCA\x02\x0DTest\\ProtoBuf\xE2\x02\x16Test\\Metadata\\ProtoBufb\x06proto3"
        , true);

        static::$is_initialized = true;
    }
}

