<?php

use WSSC\IWebSocketMessage;
use WSSC\IConnection;
use WSSC\WebSocketException;

class ServerMessageHandler implements IWebSocketMessage {
    /*
     *  if You need to parse URI context like /messanger/chat/JKN324jn4213
     *  You can do so by placing URI parts into an array - $pathParams, when Socket will receive a connection 
     *  this variable will be appropriately set to key => value pairs, ex.: ':context' => 'chat'
     *  Otherwise leave $pathParams as an empty array
     */

    public $pathParams = [':entity', ':context', ':token'];
    private $clients = [];

    public function onOpen(IConnection $conn) {
        $this->clients[] = $conn;
        echo 'Connection opend, total clients: ' . count($this->clients) . PHP_EOL;
    }

    public function onMessage(IConnection $recv, $msg) {        
        $recv->send($msg);
    }

    public function onClose(IConnection $conn) {
        unset($this->clients[array_search($conn, $this->clients)]);
        $conn->close();
    }

    public function onError(IConnection $conn, WebSocketException $ex) {
        echo 'Error occured: '.$ex->printStack();
    }

}