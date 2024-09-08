<?php

declare(strict_types=1);

namespace Test;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Phluxor\WebSocket\Client;
use Phluxor\WebSocket\Server;
use PHPUnit\Framework\TestCase;
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
                go(function () {
                    $client = new Client('localhost', 9501);
                    $client->connect();
                    $this->assertTrue($client->error()->isError());
                    $client->close();
                });
                $server->stop();
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
                $server = new Server($logger, 'localhost', 9501);
                $process = new StubRequestProcess(new StreamService());
                $server->registerService($process->getName(), $process);
                go(function () use ($server) {
                    $server->start();
                });
                \Swoole\Coroutine::sleep(1);
                $server->stop();
            });
            \Swoole\Coroutine\go(function () {
                $client = new Client('localhost', 9501);
                $stream = new StreamClient($client->connect());
                $reply = $stream->FetchResponse(new HelloRequest(['name' => 'ytake']));
                // regex hello number
                $this->assertMatchesRegularExpression('/^hello \d+$/', $reply->getMessage());
                $client->close();
            });
        });
    }

    public function testShouldReReceiveMessage(): void
    {
        run(function () {
            $messageOne = '';
            $messageTwo = '';
            \Swoole\Coroutine\go(function () {
                $logger = $this->logger();
                $server = new Server($logger, 'localhost', 9501);
                $process = new StubRequestProcess(new StreamService());
                $server->registerService($process->getName(), $process);
                go(function () use ($server) {
                    $server->start();
                });
                \Swoole\Coroutine::sleep(2);
                $server->stop();
            });
            \Swoole\Coroutine\go(function () use (&$messageOne) {
                $client = new Client('localhost', 9501);
                $stream = new StreamClient($client->connect());
                $reply = $stream->FetchResponse(new HelloRequest(['name' => 'ytake']));
                // regex hello number
                $messageOne = $reply->getMessage();
                $this->assertMatchesRegularExpression('/^hello \d+$/', $messageOne);
                $client->close();
            });
            \Swoole\Coroutine\go(function () use (&$messageTwo) {
                \Swoole\Coroutine::sleep(1);
                $client = new Client('localhost', 9501);
                $stream = new StreamClient($client->connect());
                $reply = $stream->FetchResponse(new HelloRequest(['name' => 'ytake']));
                $messageTwo = $reply->getMessage();
                // regex hello number
                $this->assertMatchesRegularExpression('/^hello \d+$/', $messageTwo);
                $client->close();
            });
            \Swoole\Coroutine::sleep(3);
            $this->assertNotSame($messageOne, $messageTwo);
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
