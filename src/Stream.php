<?php

declare(strict_types=1);

namespace Phluxor\WebSocket;

use Exception;
use Phluxor\WebSocket\Exception\ConnectionClosedException;
use Phluxor\WebSocket\Exception\WebSocketException;
use Swoole\WebSocket\CloseFrame;

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
                if (!$frame instanceof \Swoole\WebSocket\Frame) {
                    $this->request->websocket->close();
                    throw new WebSocketException('websocket frame error');
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
}
