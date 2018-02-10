<?php

namespace WSSC\Contracts;
use WSSC\Exceptions\WebSocketException;


/**
 *
 * @author Arthur Kushman
 * @property array $pathParams
 */
interface WebSocketMessageContract extends WebSocketContract, MessageContract
{
    /**
     * You may want to implement these methods to bring ping/pong events
     * @param ConnectionContract $conn
     * @param string $msg
     * @throws WebSocketException
     */
    public function onPing(ConnectionContract $conn, $msg);

    /**
     * @param ConnectionContract $conn
     * @param $msg
     * @return mixed
     * @throws WebSocketException
     */
    public function onPong(ConnectionContract $conn, $msg);
}
