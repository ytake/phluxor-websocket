<?php

declare(strict_types=1);

namespace Phluxor\WebSocket;

use Exception;

class BaseStub
{
    /** @var array{string, string}|array{} */
    private array $deserialize = [];

    public function __construct(
        private readonly ClientInterface $client
    ) {
    }

    /**
     * @param array{string, string}|array{} $deserialize
     * @param mixed $value
     * @return \Google\Protobuf\Internal\Message|null
     * @throws Exception
     */
    protected function deserializeResponse(
        array $deserialize,
        mixed $value
    ): ?\Google\Protobuf\Internal\Message {
        if ($value === null) {
            return null;
        }
        if (count($deserialize) === 0) {
            return null;
        }
        [$className, $deserializeFunc] = $deserialize;
        /** @var \Google\Protobuf\Internal\Message $className */
        $obj = new $className();
        $obj->mergeFromString($value); // @phpstan-ignore-line
        return $obj;
    }

    /**
     * @param string $method
     * @param \Google\Protobuf\Internal\Message $request
     * @param array{0: string, 1: string} $deserialize
     * @return \Google\Protobuf\Internal\Message|null
     * @throws Exception
     */
    protected function serverRequest(
        string $method,
        \Google\Protobuf\Internal\Message $request,
        array $deserialize
    ): ?\Google\Protobuf\Internal\Message {
        $this->deserialize = $deserialize;
        $this->client->send($method, $request);
        $data = $this->client->recv(-1);
        return $this->deserializeResponse($deserialize, $data);
    }

    /**
     * @return \Google\Protobuf\Internal\Message|null
     * @throws Exception
     */
    protected function getData(): ?\Google\Protobuf\Internal\Message
    {
        $data = $this->client->recv();
        return $this->deserializeResponse($this->deserialize, $data);
    }
}
