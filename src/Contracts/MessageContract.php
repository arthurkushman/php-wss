<?php

namespace WSSC\Contracts;
/**
 *
 * @author Arthur Kushman
 */
interface MessageContract
{
    public function onMessage(ConnectionContract $recv, $msg);
}
