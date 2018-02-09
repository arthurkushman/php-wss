<?php

namespace WSSC\Contracts;

use WSSC\Exceptions\WebSocketException;

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
