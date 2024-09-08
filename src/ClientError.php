<?php

declare(strict_types=1);

namespace Phluxor\WebSocket;

readonly class ClientError
{
    public function __construct(
        public string $message,
        public int $code
    ) {
    }

    public function isError(): bool
    {
        return $this->code !== 0;
    }
}
