<?php

declare(strict_types=1);

namespace Phluxor\WebSocket;

interface RequestHandlerInterface
{
    /**
     * @param Request $request
     * @return MessageInterface|null
     */
    public function handle(Request $request): ?MessageInterface;
}
