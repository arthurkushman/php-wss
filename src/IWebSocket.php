<?php

namespace WSSC;
/**
 *
 * @author Arthur Kushman
 */
interface IWebSocket
{
    public function onOpen(IConnection $conn);

    public function onClose(IConnection $conn);

    public function onError(IConnection $conn, WebSocketException $ex);
}
