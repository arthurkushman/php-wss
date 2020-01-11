<?php

namespace WSSCTEST;

use PHPUnit\Framework\TestCase;
use WSSC\Components\ServerConfig;
use WSSC\WebSocketServer;

class WebSocketServerTest extends TestCase
{
    /**
     * @test
     * @throws \WSSC\Exceptions\WebSocketException
     * @throws \WSSC\Exceptions\ConnectionException
     */
    public static function is_server_running()
    {
        echo 'Running server...' . PHP_EOL;

        $config = new ServerConfig();
        $config->setClientsPerFork(2500);
        $config->setStreamSelectTimeout(2 * 3600);

        $websocketServer = new WebSocketServer(new ServerHandler(), $config);
        $websocketServer->run();
    }
}