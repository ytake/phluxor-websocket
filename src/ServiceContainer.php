<?php

declare(strict_types=1);

namespace Phluxor\WebSocket;

use Exception;
use Phluxor\WebSocket\Exception\InvokeException;
use Phluxor\WebSocket\Exception\RequestMessageException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionType;
use Throwable;
use TypeError;

final class ServiceContainer
{
    public readonly string $name;

    /** @var array<non-empty-string, array{name: non-empty-string, inputClass: ?ReflectionType, returnClass: ?ReflectionType}> */
    private array $methods;

    /**
     * @param ServiceInterface $service
     * @throws Exception
     */
    public function __construct(
        private readonly ServiceInterface $service
    ) {
        $this->name = $service::NAME;
        $this->methods = $this->discoverMethods($service);
    }

    public function getService(): ServiceInterface
    {
        return $this->service;
    }

    /**
     * @return array<int, array{name: non-empty-string, inputClass: ?ReflectionType, returnClass: ?ReflectionType}>
     */
    public function getMethods(): array
    {
        return array_values($this->methods);
    }

    /**
     * @throws Exception
     */
    public function handle(Request $request): string
    {
        $method = $request->method;
        $context = $request->getContext();
        /** @var callable $callable */
        $callable = [$this->service, $method];
        $ic = $this->methods[$method]['inputClass'];
        if ($ic instanceof ReflectionNamedType) {
            $class = $ic->getName();
        } else {
            throw new RequestMessageException('input message class not found');
        }
        /** @var \Google\Protobuf\Internal\Message $message */
        $message = new $class();
        $message->mergeFromString($request->payload);
        try {
            $result = $callable($context, $message);
        } catch (TypeError $e) {
            throw new InvokeException($e->getMessage(), Status::INTERNAL, $e);
        }
        try {
            $output = $result->serializeToString();
        } catch (Throwable $e) {
            throw new InvokeException($e->getMessage(), Status::INTERNAL, $e);
        }
        return $output;
    }

    /**
     * @param ServiceInterface $service
     * @return array<non-empty-string, array{name: non-empty-string, inputClass: ?ReflectionType, returnClass: ?ReflectionType}>
     * @throws Exception
     */
    private function discoverMethods(ServiceInterface $service): array
    {
        $reflection = new ReflectionObject($service);
        $methods = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Check if its a gRPC method before doing this check
            if (count($method->getParameters()) > 0 && $method->getParameters()[0]->getName() == 'ctx') {
                // This is a gRPC method
                if ($method->getNumberOfParameters() !== 2) {
                    throw new Exception('error method');
                }
                [, $input] = $method->getParameters();

                $methods[$method->getName()] = [
                    'name' => $method->getName(),
                    'inputClass' => $input->getType(),
                    'returnClass' => $method->getReturnType()
                ];
            }
        }
        return $methods;
    }
}
