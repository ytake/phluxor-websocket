<?php

declare(strict_types=1);

namespace Phluxor\WebSocket\Exception;

use Phluxor\WebSocket\Status;
use RuntimeException;
use Throwable;

class WebSocketException extends RuntimeException
{
    protected const int CODE = Status::UNKNOWN;

    final public function __construct(
        string $message = '',
        int $code = null,
        Throwable $previous = null
    ) {
        parent::__construct($message, ($code ?? static::CODE), $previous);
    }
}
