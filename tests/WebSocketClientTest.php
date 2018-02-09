<?php

namespace WSSCTEST;

use PHPUnit\Framework\TestCase;
use WSSC\WebSocketClient;

class WebSocketClientTest extends TestCase
{

    /**
     * @test
     */
    public function is_client_connected()
    {
        $client = new WebSocketClient('ws://localhost:8000/notifications/messanger/vkjsndfvjn23243');
        $client->send('{"user_id" : 123}');
        echo $client->receive();
        $client->close();
    }
}
