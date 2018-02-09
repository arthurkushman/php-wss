<?php

namespace WSSCTEST;

use WSSC\Contracts\ConnectionContract;
use WSSC\Contracts\WebSocketMessageContract;
use WSSC\Exceptions\WebSocketException;

class ServerMessageHandler implements WebSocketMessageContract
{

    /*
     *  if You need to parse URI context like /messanger/chat/JKN324jn4213
     *  You can do so by placing URI parts into an array - $pathParams, when Socket will receive a connection 
     *  this variable will be appropriately set to key => value pairs, ex.: ':context' => 'chat'
     *  Otherwise leave $pathParams as an empty array
     */

    public $pathParams = [':entity', ':context', ':token'];
    private $clients = [];

    public function onOpen(ConnectionContract $conn)
    {
        $this->clients[] = $conn;
        echo 'Connection opend, total clients: ' . count($this->clients) . PHP_EOL;
    }

    public function onMessage(ConnectionContract $recv, $msg)
    {
        echo 'Received message:  ' . $msg . PHP_EOL;
        $recv->send($msg);
    }

    public function onClose(ConnectionContract $conn)
    {
        unset($this->clients[array_search($conn, $this->clients)]);
        $conn->close();
    }

    /**
     * @param ConnectionContract $conn
     * @param WebSocketException $ex
     */
    public function onError(ConnectionContract $conn, WebSocketException $ex)
    {
        echo 'Error occured: ' . $ex->printStack();
    }

}
