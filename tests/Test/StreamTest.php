<?php

declare(strict_types=1);

namespace Test;

use Google\Protobuf\Internal\Message;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Phluxor\WebSocket\Client;
use Phluxor\WebSocket\Context;
use Phluxor\WebSocket\Exception\WebSocketException;
use Phluxor\WebSocket\Request;
use Phluxor\WebSocket\Server;
use Phluxor\WebSocket\Stream;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Response;
use Test\ProtoBuf\GreeterClient;
use Test\ProtoBuf\GreeterService;
use Test\ProtoBuf\HelloReply;
use Test\ProtoBuf\HelloRequest;

use function Swoole\Coroutine\run;

class StreamTest extends TestCase
{
    public function testWhenNoWebSocketShouldThrowException(): void
    {
        $this->expectException(WebSocketException::class);
        $request = new Request(new Context([]), 'payload', 'hoge', new Response());
        $stream = new Stream($request, new HelloRequest());
        $stream->recv();
    }

    public function testShouldReReceiveMessage(): void
    {
        run(function () {
            \Swoole\Coroutine\go(function () {
                $logger = $this->logger();
                $server = new Server($logger, 'localhost', 9504);
                $stream = new GreeterService();
                $stream->assert(function (Message $message) {
                    TestCase::assertInstanceOf(HelloReply::class, $message);
                });
                $process = new StubReplyStream($stream);
                $server->registerService($process->getName(), $process);
                go(function () use ($server) {
                    $server->start();
                });
                \Swoole\Coroutine::sleep(3);
                $server->stop();
            });
            $client = new Client('localhost', 9504);
            $stream = new GreeterClient($client->connect());
            $stream->SayHello(new HelloRequest(['name' => 'ytake']));
            $client->close();
        });
    }

    private function logger(): Logger
    {
        $log = new Logger('Phluxor');
        $log->useLoggingLoopDetection(false);
        $log->pushHandler(new TestHandler());
        return $log;
    }
}
