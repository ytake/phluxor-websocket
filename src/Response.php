<?php

declare(strict_types=1);

namespace Phluxor\WebSocket;

readonly class Response implements MessageInterface
{
    public function __construct(
        public Context $context,
        private ?string $payload
    ) {
    }

    public function getPayload(): string
    {
        return $this->payload ?? '';
    }
}
