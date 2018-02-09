<?php

namespace WSSC\Contracts;
/**
 *
 * @author Arthur Kushman
 */
interface WebSocketContract
{
    public function onOpen(ConnectionContract $conn);

    public function onClose(ConnectionContract $conn);

    public function onError(ConnectionContract $conn, WebSocketException $ex);
}
