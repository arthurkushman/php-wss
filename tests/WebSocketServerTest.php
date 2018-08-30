<?php

namespace WSSCTEST;

use PHPUnit\Framework\TestCase;
use WSSC\WebSocketServer;

class WebSocketServerTest extends TestCase
{

    /**
     * @test
     */
    public static function is_server_running()
    {
        echo 'Running server...' . PHP_EOL;
        $websocketServer = new WebSocketServer(new ServerHandler(), [
            'host'                   => '0.0.0.0',
            'port'                   => 8000,
            'clients_per_fork_limit' => 2500,
        ]);
        $websocketServer->run();
    }
}