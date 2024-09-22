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

use Exception;
use Phluxor\WebSocket\Exception\ConnectionClosedException;
use Phluxor\WebSocket\Exception\WebSocketException;
use Swoole\WebSocket\CloseFrame;
use Swoole\WebSocket\Frame;

readonly class Stream
{
    public function __construct(
        private Request $request,
        private \Google\Protobuf\Internal\Message $classString
    ) {
    }

    /**
     * @throws Exception
     */
    public function recv(): \Google\Protobuf\Internal\Message
    {
        $frame = $this->request->websocket->recv();
        if ($frame === '') {
            $this->request->websocket->close();
            throw new ConnectionClosedException('websocket connection closed');
        } else {
            if ($frame === false) {
                $this->request->websocket->close();
                throw new WebSocketException('websocket frame error');
            } else {
                if (!$frame instanceof Frame) {
                    $this->request->websocket->close();
                    throw new WebSocketException('websocket frame error');
                }
                if ($frame->opcode === WEBSOCKET_OPCODE_PING) {
                    $pong = new Frame();
                    $pong->opcode = WEBSOCKET_OPCODE_PONG;
                    $this->request->websocket->push($pong);
                }
                if ($frame->data == 'close' || get_class($frame) === CloseFrame::class) {
                    $this->request->websocket->close();
                    throw new ConnectionClosedException('websocket connection closed');
                }
                $data = $frame->data ? substr($frame->data, 5) : '';
                $message = $this->classString;
                $message->mergeFromString($data);
                return $message;
            }
        }
    }

    public function close(): void
    {
        $this->request->websocket->close();
    }
}
