<?php

/**
 * Copyright 2024 Yuuki Takezawa <yuuki.takezawa@comnect.jp.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace Phluxor\WebSocket;

use Phluxor\WebSocket\Exception\ClientException;
use Phluxor\WebSocket\Exception\ConnectionRefusedException;
use Swoole\Coroutine;
use Swoole\WebSocket\Frame;

class Client implements ClientInterface
{
    private Coroutine\Http\Client $client;
    private ?Coroutine\Channel $channel = null;
    private Coroutine\Channel $closed;
    private const int RESPONSE_TIMEOUT = -1;
    private const array CLIENT_ERR_CODES = [104, 32, 5001];
    /** @var array{timeout:string, open_eof_check:bool, package_max_length:int, max_retries:int, receive_timeout:int, force_reconnect:bool, keep_alive:bool} $settings */
    private const array DEFAULT_SETTINGS = [
        'timeout' => self::RESPONSE_TIMEOUT,
        'open_eof_check' => true,
        'package_max_length' => 2097152, // 2MB
        'max_retries' => 10,
        'receive_timeout' => self::RESPONSE_TIMEOUT,
        'force_reconnect' => false,
        'keep_alive' => true,
    ];

    /** @var array{timeout:string, open_eof_check:bool, package_max_length:int, max_retries:int, receive_timeout:int, force_reconnect:bool, keep_alive:bool} $settings */
    private array $settings;

    /**
     * @param string $host
     * @param int $port
     * @param bool $ssl
     * @param array{string, mixed} $settings
     */
    public function __construct(
        string $host,
        int $port,
        bool $ssl = false,
        array $settings = []
    ) {
        $this->settings = array_merge(self::DEFAULT_SETTINGS, $settings);
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

    public function connect(): ClientInterface
    {
        $this->client->set($this->settings);
        return $this;
    }

    public function close(): void
    {
        $this->closed->push(true);
        $this->safeCloseChannel();
    }

    public function clientClose(): void
    {
        $this->client->close();
    }

    /**
     * 冗長なロジックを抽出し簡潔に整理
     */
    public function reconnect(string $upgradePath): void
    {
        for ($retry = $this->settings['max_retries']; $retry > 0; $retry--) {
            $this->safeCloseChannel();
            $this->connect();

            if ($this->client->upgrade($upgradePath)) {
                return;
            }
            Coroutine::sleep(0.02);
        }

        throw new ClientException(
            swoole_strerror($this->client->errCode, 9) . " {$this->client->host}:{$this->client->port}",
            $this->client->errCode
        );
    }

    /**
     * クライアントエラー時以外はデータ送信
     */
    public function send(string $method, \Google\Protobuf\Internal\Message $message): bool
    {
        if (!$this->client->connected) {
            if ($this->client->errCode == 61) {
                throw new ConnectionRefusedException(
                    swoole_strerror($this->client->errCode, 9)
                );
            }
            if (!$this->client->upgrade($method)) {
                $this->reconnect($method);
            }

            $this->startReceivingLoop();
        }

        if ($this->sendMessage($message)) {
            $this->channel = new Coroutine\Channel(1);
            return true;
        }
        return false;
    }

    public function recv(int $timeout = self::RESPONSE_TIMEOUT): mixed
    {
        return $this->channel?->pop($timeout) ?: null;
    }

    public function push(\Google\Protobuf\Internal\Message $message, bool $end = false): bool
    {
        return $this->sendMessage($message);
    }

    private function sendMessage(\Google\Protobuf\Internal\Message $message): bool
    {
        $payload = $message->serializeToString();
        return $this->client->push(
            pack('CN', 0, strlen($payload)) . $payload
        );
    }

    // 不要なエラーループロジックは整理
    public function hasConnectionError(): bool
    {
        return $this->closed->errCode != 0;
    }

    public function error(): ClientError
    {
        return new ClientError($this->client->errMsg, $this->client->errCode);
    }

    private function safeCloseChannel(): void
    {
        $this->channel?->close();
    }

    private function startReceivingLoop(): void
    {
        go(function () {
            while (true) {
                if (in_array($this->client->errCode, self::CLIENT_ERR_CODES)) {
                    $this->safeCloseChannel();
                    $this->client->close();
                    $this->closed->close();
                    break;
                }

                $response = $this->client->recv($this->settings['timeout']);
                if ($response instanceof Frame && $response->data && $this->channel) {
                    $this->channel->push(substr($response->data, 5));
                }

                if ($this->closed->pop(0.01)) {
                    $this->safeCloseChannel();
                    $this->client->close();
                    break;
                }
            }
        });
    }
}
