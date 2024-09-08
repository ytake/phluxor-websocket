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
