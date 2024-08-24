<?php

declare(strict_types=1);

namespace Phluxor\WebSocket\Exception;

use Phluxor\WebSocket\Status;

class NotFoundException extends WebSocketException
{
    protected const int CODE = Status::NOT_FOUND;
}
