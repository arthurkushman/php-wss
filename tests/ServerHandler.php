<?php

namespace WSSCTEST;

use WSSC\Contracts\ConnectionContract;
use WSSC\Contracts\WebSocket;
use WSSC\Exceptions\WebSocketException;

class ServerHandler extends WebSocket
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

    /**
     * You may want to implement these methods to bring ping/pong events
     * @param ConnectionContract $conn
     * @param string $msg
     */
    public function onPing(ConnectionContract $conn, $msg)
    {
        // TODO: Implement onPing() method.
    }

    /**
     * @param ConnectionContract $conn
     * @param $msg
     * @return mixed
     */
    public function onPong(ConnectionContract $conn, $msg)
    {
        // TODO: Implement onPong() method.
    }
}
