<?php

namespace WSSC\Contracts;


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
     * @param type $msg
     */
//    function onPing(IConnection $conn, $msg);
//    function onPong(IConnection $conn, $msg);
}
