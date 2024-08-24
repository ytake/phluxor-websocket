<?php

declare(strict_types=1);

namespace Phluxor\WebSocket\Exception;

use Phluxor\WebSocket\Status;

class ServiceException extends WebSocketException
{
    protected const int CODE = Status::INTERNAL;
}
