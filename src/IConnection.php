<?php

namespace WSSC;
/**
 *
 * @author Arthur Kushman
 */
interface IConnection
{
    public function send($data);

    public function close();
}
