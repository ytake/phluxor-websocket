<?php

declare(strict_types=1);

namespace Test;

use Phluxor\WebSocket\Exception\InvokeException;
use Phluxor\WebSocket\MessageInterface;
use Phluxor\WebSocket\Request;
use Phluxor\WebSocket\RequestHandlerInterface;
use Phluxor\WebSocket\ServiceInterface;
use Phluxor\WebSocket\Status;
use Test\ProtoBuf\HelloRequest;

class StubRequestProcess implements RequestHandlerInterface
{
    private string $name;

    /**
     * @param ServiceInterface $service
     */
    public function __construct(
        private ServiceInterface $service
    ) {
        $this->name = $service::NAME;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function handle(Request $request): ?MessageInterface
    {
        $method = $request->method;
        $context = $request->getContext();
        /** @var callable $callable */
        $callable = [$this->service, $method];
        try {
            $callable($context, new HelloRequest());
        } catch (\TypeError $e) {
            throw new InvokeException($e->getMessage(), Status::INTERNAL, $e);
        }
        return null;
    }
}
