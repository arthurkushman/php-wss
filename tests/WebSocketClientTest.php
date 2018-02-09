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
        echo 'Running client...' . PHP_EOL;
        $recvMsg = '{"user_id" : 123}';
        $client = new WebSocketClient('ws://localhost:8000/notifications/messanger/vkjsndfvjn23243');
        $client->send($recvMsg);
        $recv = $client->receive();
        $this->assertEquals($recv, $recvMsg);
    }
}
