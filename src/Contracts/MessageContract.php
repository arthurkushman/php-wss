<?php

namespace WSSC\Contracts;

use WSSC\Exceptions\WebSocketException;

/**
 *
 * @author Arthur Kushman
 */
interface MessageContract
{
    /**
     * @param ConnectionContract $recv
     * @param $msg
     * @return mixed
     * @throws WebSocketException
     */
    public function onMessage(ConnectionContract $recv, $msg);
}
