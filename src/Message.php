<?php

declare(strict_types=1);

namespace Phluxor\WebSocket;

final readonly class Message implements MessageInterface
{
    public function __construct(
        public ContextInterface $context,
        public \Google\Protobuf\Internal\Message $message
    ) {
    }
}
