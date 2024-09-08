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

interface ClientInterface
{
    /**
     * @param array{string, mixed} $settings
     * @return $this
     */
    public function set(array $settings): self;

    public function connect(): self;

    public function close(): void;

    /**
     * Send message to remote endpoint, either end the stream or not depending on $mode of the client
     * @param string $method
     * @param \Google\Protobuf\Internal\Message $message
     * @return bool
     */
    public function send(string $method, \Google\Protobuf\Internal\Message $message): bool;

    /**
     * Receive the data from a stream in the established connection based on streamId.
     * @param int $timeout
     * @return mixed
     */
    public function recv(int $timeout = -1): mixed;

    /**
     * Push message to the remote endpoint, used in client side streaming mode.
     * @param \Google\Protobuf\Internal\Message $message
     * @param bool $end
     * @return bool
     */
    public function push(
        \Google\Protobuf\Internal\Message $message,
        bool $end = false
    ): bool;
}
