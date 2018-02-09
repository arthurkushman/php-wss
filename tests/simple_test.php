<?php

require_once './src/ICommons.php';
require_once './src/IConnection.php';
require_once './src/ConnectionImpl.php';
require_once './src/IMessage.php';
require_once './src/IWebSocket.php';
require_once './src/IWebSocketServer.php';
require_once './src/IWebSocketMessage.php';
require_once './src/WebSocketServer.php';
require_once './src/WebSocketClient.php';
require_once 'ServerMessageContractHandler.php';

use WSSC\WebSocketServer;
use WSSC\WebSocketClient;

/**
 * Create by Arthur Kushman
 */
$websocketServer = new WebSocketServer(new ServerMessageContractHandler(), [
    'host' => '0.0.0.0',
    'port' => 8000
        ]);
$websocketServer->run();
