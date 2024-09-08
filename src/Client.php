<?php

declare(strict_types=1);

namespace Phluxor\WebSocket;

use Phluxor\WebSocket\Exception\ClientException;
use Phluxor\WebSocket\Exception\ConnectionRefusedException;
use Swoole\Coroutine;
use Swoole\WebSocket\Frame;

class Client implements ClientInterface
{
    private Coroutine\Http\Client $client;

    /** @var Coroutine\Channel|null */
    private ?Coroutine\Channel $channel = null;
    private Coroutine\Channel $closed;

    /** @var array{timeout: int, open_eof_check: bool, package_max_length: int, max_retries: int, receive_timeout: int, force_reconnect: bool} */
    private array $settings = [
        'timeout' => -1,
        'open_eof_check' => true,
        'package_max_length' => 2 * 1024 * 1024,
        'max_retries' => 10,
        'receive_timeout' => -1,
        'force_reconnect' => false,
        'keep_alive' => true,
    ];

    /**
     * @param string $host
     * @param int $port
     * @param bool $ssl
     * @param array<string, mixed> $settings
     */
    public function __construct(
        string $host,
        int $port,
        bool $ssl = false,
        array $settings = []
    ) {
        $this->settings = array_merge($this->settings, $settings); // @phpstan-ignore-line
        $this->client = new Coroutine\Http\Client($host, $port, $ssl);
        $this->closed = new Coroutine\Channel(1);
    }

    /**
     * @param array{string, mixed} $settings
     * @return $this
     */
    public function set(array $settings): self
    {
        $this->settings = array_merge($this->settings, $settings);
        return $this;
    }

    /**
     * Establish a connection to the remote endpoint
     */
    public function connect(): ClientInterface
    {
        $this->client->set($this->settings);
        go(function () {
            while (true) {
                $response = $this->client->recv($this->settings['timeout']);
                if ($response instanceof Frame) {
                    if ($response->data) {
                        if ($this->channel instanceof Coroutine\Channel) {
                            $this->channel->push(substr($response->data, 5));
                        }
                    }
                }
                if ($this->closed->pop(0.02)) {
                    break;
                }
            }
        });
        return $this;
    }

    /**
     * @return void
     */
    public function close(): void
    {
        $this->closed->push(true);
        $this->client->socket?->close();
        $this->client->close();
    }

    public function reconnect(string $upgradePath): void
    {
        $retry = $this->settings['max_retries'];
        while ($retry-- > 0) {
            if ($this->channel instanceof Coroutine\Channel) {
                $this->channel->close();
            }
            $this->connect();
            $result = $this->client->upgrade($upgradePath);
            if ($result) {
                break;
            }
            Coroutine::sleep(0.02);
        }
        if ($this->client->errCode !== 0) {
            throw new ClientException(
                swoole_strerror($this->client->errCode, 9) . " {$this->client->host}:{$this->client->port}",
                $this->client->errCode
            );
        }
    }

    /**
     * Send message to remote endpoint, either end the stream or not depending on $mode of the client
     * @param string $method
     * @param \Google\Protobuf\Internal\Message $message
     * @return bool
     */
    public function send(
        string $method,
        \Google\Protobuf\Internal\Message $message
    ): bool {
        if (!$this->client->connected) {
            if ($this->client->errCode == 61) {
                throw new ConnectionRefusedException(
                    swoole_strerror($this->client->errCode, 9)
                );
            }
            $conn = $this->client->upgrade($method);
            if (!$conn) {
                $this->reconnect($method);
            }
        }
        $result = $this->sendMessage($message);
        if ($result) {
            $this->channel = new Coroutine\Channel(1);
            return true;
        }
        return false;
    }

    /**
     * Receive the data from a stream in the established connection based on streamId.
     * @param int $timeout
     * @return mixed
     */
    public function recv(int $timeout = -1): mixed
    {
        $data = false;
        if ($this->channel instanceof Coroutine\Channel) {
            $data = $this->channel->pop((int)$this->settings['receive_timeout']);
        }
        if (!$data) {
            return null;
        }
        return $data;
    }

    /**
     * Push message to the remote endpoint, used in client side streaming mode.
     * @param \Google\Protobuf\Internal\Message $message
     * @param bool $end
     * @return bool
     */
    public function push(
        \Google\Protobuf\Internal\Message $message,
        bool $end = false
    ): bool {
        $payload = $message->serializeToString();
        return $this->client->push(
            pack('CN', 0, strlen($payload)) . $payload,
        );
    }

    /**
     * @param \Google\Protobuf\Internal\Message $message
     * @return bool
     */
    private function sendMessage(
        \Google\Protobuf\Internal\Message $message
    ): bool {
        $payload = $message->serializeToString();
        return $this->client->push(pack('CN', 0, strlen($payload)) . $payload);
    }

    /**
     * @return ClientError
     */
    public function error(): ClientError
    {
        return new ClientError(
            $this->client->errMsg,
            $this->client->errCode
        );
    }
}
