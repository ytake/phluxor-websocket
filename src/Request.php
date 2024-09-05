<?php

declare(strict_types=1);

namespace Phluxor\WebSocket;

final class Request implements MessageInterface
{
    public function __construct(
        private Context $context,
        public readonly string $service,
        public readonly string $method,
        public readonly \Swoole\Http\Response $websocket
    ) {
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function withContext(Context $context): self
    {
        $this->context = $context;
        return $this;
    }
}
