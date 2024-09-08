<?php

/**
 * Copyright 2024 Yuuki Takezawa <yuuki.takezawa@comnect.jp.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace Phluxor\WebSocket;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @psalm-import-type TValues from ContextInterface
 * @implements IteratorAggregate<string, mixed>
 * @implements ArrayAccess<string, mixed>
 */
final class Context implements ContextInterface, IteratorAggregate, Countable, ArrayAccess
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        private array $values
    ) {
    }

    public function withValue(string $key, mixed $value): ContextInterface
    {
        $context = clone $this;
        $context->values[$key] = $value;
        return $context;
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function offsetExists(mixed $offset): bool
    {
        assert(is_string($offset), 'Offset argument must be a type of string');

        return isset($this->values[$offset]) || array_key_exists($offset, $this->values);
    }

    public function offsetGet(mixed $offset): mixed
    {
        assert(is_string($offset), 'Offset argument must be a type of string');

        return $this->values[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        assert(is_string($offset), 'Offset argument must be a type of string');

        $this->values[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        assert(is_string($offset), 'Offset argument must be a type of string');
        unset($this->values[$offset]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    public function count(): int
    {
        return count($this->values);
    }
}
