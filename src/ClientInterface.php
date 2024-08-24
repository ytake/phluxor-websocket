<?php

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
