<?php

declare(strict_types=1);

namespace Phluxor\WebSocket;

readonly class Response implements MessageInterface
{
    public function __construct(
        public Context $context,
        public string $payload
    ) {
    }
}
