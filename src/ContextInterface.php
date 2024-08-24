<?php

declare(strict_types=1);

namespace Phluxor\WebSocket;

/**
 * @psalm-type TValues = array<string, mixed>
 */
interface ContextInterface
{
    /**
     * Create context with new value.
     * @param non-empty-string $key
     * @return $this
     */
    public function withValue(string $key, mixed $value): self;

    /**
     * Get context value or return null.
     * @param non-empty-string $key
     */
    public function getValue(string $key, mixed $default = null): mixed;

    /**
     * @return TValues
     */
    public function getValues(): array;
}
