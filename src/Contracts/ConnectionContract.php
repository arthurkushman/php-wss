<?php

namespace WSSC\Contracts;
/**
 *
 * @author Arthur Kushman
 */
interface ConnectionContract
{
    public function send($data);

    public function close();
}
