<?php

namespace WSSC\Contracts;

use WSSC\Exceptions\WebSocketException;

/**
 *
 * @author Arthur Kushman
 */
interface WebSocketContract
{
    /**
     * @param ConnectionContract $conn
     * @return mixed
     */
    public function onOpen(ConnectionContract $conn);

    /**
     * @param ConnectionContract $conn
     * @return mixed
     * @throws WebSocketException
     */
    public function onClose(ConnectionContract $conn);

    /**
     * @param ConnectionContract $conn
     * @param WebSocketException $ex
     * @return mixed
     */
    public function onError(ConnectionContract $conn, WebSocketException $ex);
}
