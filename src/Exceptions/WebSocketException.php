<?php

namespace WSSC\Exceptions;

class WebSocketException extends \Exception
{
    public function printStack()
    {
        echo $this->getFile() . ' ' . $this->getLine() . ' ' . $this->getMessage() . PHP_EOL;
        echo $this->getTraceAsString();
    }
}
