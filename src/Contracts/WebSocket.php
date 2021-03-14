<?php

namespace WSSC\Contracts;

use WSSC\Exceptions\WebSocketException;


/**
 *
 * @author Arthur Kushman
 */
abstract class WebSocket implements WebSocketContract, MessageContract
{
    /**
     * @var array
     */
    public array $pathParams = [];

    /**
     * You may want to implement these methods to bring ping/pong events
     * @param ConnectionContract $conn
     * @param string $msg
     * @throws WebSocketException
     */
    abstract public function onPing(ConnectionContract $conn, $msg);

    /**
     * @param ConnectionContract $conn
     * @param $msg
     * @throws WebSocketException
     */
    abstract public function onPong(ConnectionContract $conn, $msg);
}
