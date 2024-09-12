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

use Exception;

class BaseStub
{
    /** @var array{string, string}|array{} */
    private array $deserialize = [];

    public function __construct(
        private readonly ClientInterface $client
    ) {
    }

    public function close(): void
    {
        $this->client->close();
    }

    public function hasConnectionError(): bool
    {
        return $this->client->hasConnectionError();
    }

    /**
     * @param array{string, string}|array{} $deserialize
     * @param mixed $value
     * @return \Google\Protobuf\Internal\Message|null
     * @throws Exception
     */
    protected function deserializeResponse(
        array $deserialize,
        mixed $value
    ): ?\Google\Protobuf\Internal\Message {
        if ($value === null) {
            return null;
        }
        if (count($deserialize) === 0) {
            return null;
        }
        // TODO: Handle multiple deserializer in the future
        [$className, $deserializeFunc] = $deserialize;
        /** @var \Google\Protobuf\Internal\Message $className */
        $obj = new $className();
        $obj->mergeFromString($value); // @phpstan-ignore-line
        return $obj;
    }

    /**
     * @param string $method
     * @param \Google\Protobuf\Internal\Message $request
     * @param array{0: string, 1: string} $deserialize
     * @return \Google\Protobuf\Internal\Message|null
     * @throws Exception
     */
    protected function serverRequest(
        string $method,
        \Google\Protobuf\Internal\Message $request,
        array $deserialize
    ): ?\Google\Protobuf\Internal\Message {
        $this->deserialize = $deserialize;
        $this->client->send($method, $request);
        $data = $this->client->recv(1);
        return $this->deserializeResponse($deserialize, $data);
    }

    /**
     * @return \Google\Protobuf\Internal\Message|null
     * @throws Exception
     */
    protected function getData(): ?\Google\Protobuf\Internal\Message
    {
        $data = $this->client->recv();
        return $this->deserializeResponse($this->deserialize, $data);
    }
}
