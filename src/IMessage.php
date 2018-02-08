<?php

namespace WSSC;
/**
 *
 * @author Arthur Kushman
 */
interface IMessage
{
    public function onMessage(IConnection $recv, $msg);
}
