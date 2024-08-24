<?php

declare(strict_types=1);

namespace Phluxor\WebSocket;

final class Status
{
    public const int OK = 0;

    public const int CANCELLED = 1;

    public const int UNKNOWN = 2;

    public const int INVALID_ARGUMENT = 3;

    public const int DEADLINE_EXCEEDED = 4;

    public const int NOT_FOUND = 5;

    public const int ALREADY_EXISTS = 6;

    public const int PERMISSION_DENIED = 7;

    public const int RESOURCE_EXHAUSTED = 8;

    public const int FAILED_PRECONDITION = 9;

    public const int ABORTED = 10;

    public const int OUT_OF_RANGE = 11;

    public const int UNIMPLEMENTED = 12;

    public const int INTERNAL = 13;

    public const int UNAVAILABLE = 14;

    public const int DATA_LOSS = 15;

    public const int UNAUTHENTICATED = 16;
}
