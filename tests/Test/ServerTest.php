<?php

declare(strict_types=1);

namespace Test;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Phluxor\WebSocket\Client;
use Phluxor\WebSocket\Message;
use Phluxor\WebSocket\Server;
use PHPUnit\Framework\TestCase;
use Test\ProtoBuf\HelloReply;
use Test\ProtoBuf\HelloRequest;
use Test\ProtoBuf\StreamClient;
use Test\ProtoBuf\StreamService;

use function Swoole\Coroutine\run;

class ServerTest extends TestCase
{
    public function testServerStartAndStopNoErrorLog(): void
    {
        run(function () {
            \Swoole\Coroutine\go(function () {
                $logger = $this->logger();
                $server = new Server($logger, 'localhost', 9501);
                go(function () use ($server) {
                    $server->start();
                });
                \Swoole\Coroutine::sleep(0.1);
                $server->stop();
                $records = $logger->getHandlers()[0]->getRecords();
                $this->assertSameSize([], $records);
                \Swoole\Coroutine::sleep(0.1);
            });
        });
    }

    public function testShouldCreateClient(): void
    {
        run(function () {
            \Swoole\Coroutine\go(function () {
                $logger = $this->logger();
                $server = new Server($logger, 'localhost', 9501);
                go(function () use ($server) {
                    $server->start();
                });
                \Swoole\Coroutine::sleep(0.1);
                $client = new Client('localhost', 9501);
                $client->connect();
                $this->assertFalse($client->error()->isError());
                $client->close();
                $server->stop();
                \Swoole\Coroutine::sleep(0.1);
                $records = $logger->getHandlers()[0]->getRecords();
                $this->assertSameSize([], $records);
            });
        });
    }

    public function testShouldReceiveMessage(): void
    {
        run(function () {
            \Swoole\Coroutine\go(function () {
                $logger = $this->logger();
                $server = new Server($logger, 'localhost', 9502);
                $stream = new StreamService();
                $stream->assert(function (Message $message) {
                    $m = $message->message;
                    TestCase::assertInstanceOf(HelloReply::class, $m);
                    TestCase::assertMatchesRegularExpression('/^hello \d+$/', $m->getMessage());
                    return;
                });
                $process = new StubRequestProcess($stream);
                $server->registerService($process->getName(), $process);
                go(function () use ($server) {
                    $server->start();
                });
                \Swoole\Coroutine::sleep(2);
                $server->stop();
            });
            $client = new Client('localhost', 9502);
            $stream = new StreamClient($client->connect());
            $stream->FetchResponse(new HelloRequest(['name' => 'ytake']));
            $client->close();
        });
    }

    public function testShouldReReceiveMessage(): void
    {
        run(function () {
            $messages = [];
            \Swoole\Coroutine\go(function () use (&$messages) {
                $logger = $this->logger();
                $server = new Server($logger, 'localhost', 9504);
                $stream = new StreamService();
                $stream->assert(function (Message $message) use (&$messages) {
                    $m = $message->message;
                    TestCase::assertInstanceOf(HelloReply::class, $m);
                    TestCase::assertMatchesRegularExpression('/^hello \d+$/', $m->getMessage());
                    $messages[] = $m->getMessage();
                    return;
                });
                $process = new StubRequestProcess($stream);
                $server->registerService($process->getName(), $process);
                go(function () use ($server) {
                    $server->start();
                });
                \Swoole\Coroutine::sleep(3);
                $server->stop();
            });
            $client = new Client('localhost', 9504);
            $stream = new StreamClient($client->connect());
            $stream->FetchResponse(new HelloRequest(['name' => 'ytake']));
            \Swoole\Coroutine::sleep(1);
            $stream->FetchResponse(new HelloRequest(['name' => 'ytake']));
            $client->close();
            $this->assertCount(2, $messages);
            $this->assertCount(2, array_unique($messages));
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
