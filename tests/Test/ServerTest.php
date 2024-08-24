<?php

declare(strict_types=1);

namespace Test;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Phluxor\WebSocket\Client;
use Phluxor\WebSocket\Middleware\LoggingMiddleware;
use Phluxor\WebSocket\Server;
use PHPUnit\Framework\TestCase;
use Test\ProtoBuf\GreeterClient;
use Test\ProtoBuf\GreeterService;
use Test\ProtoBuf\HelloRequest;
use Test\ProtoBuf\StreamClient;
use Test\ProtoBuf\StreamService;

use function Swoole\Coroutine\run;

class ServerTest extends TestCase
{
    public function testServer(): void
    {
        run(function () {
            $chan = new \Swoole\Coroutine\Channel(1);
            $logger = $this->logger();
            $server = new Server($logger, '127.0.0.1', 9502);
            $server->registerService(new GreeterService())
                ->registerService(new StreamService())
                ->withMiddleware(new LoggingMiddleware($logger));
            go(function () use ($server) {
                $server->start();
            });
            go(function () use ($server, $chan) {
                $chan->pop();
                $server->stop();
            });

            $conn = (new Client('127.0.0.1', 9502))->connect();
            $client = new StreamClient($conn);
            $message = new HelloRequest();
            $message->setName(str_repeat('x', 10));
            $out = $client->FetchResponse($message);
            $this->assertNotSame('', $out->getMessage());
            sleep(1);
            $outTwo = $client->FetchResponse($message);
            $this->assertNotSame('', $outTwo->getMessage());
            $this->assertNotSame($out->getMessage(), $outTwo->getMessage());
            $greeter = new GreeterClient($conn);
            $message = new HelloRequest();
            $message->setName(str_repeat('x', 10));
            $outThree = $greeter->SayHello($message);
            $this->assertNotSame('', $outThree->getMessage());
            $chan->push(true);
            $conn->close();
        });
    }

    private function logger(): Logger
    {
        $log = new Logger('Phluxor');
        $log->useLoggingLoopDetection(false);
        $log->pushHandler(new StreamHandler('php://stdout', Level::Info));
        return $log;
    }
}
