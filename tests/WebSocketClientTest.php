<?php

namespace WSSCTEST;

use PHPUnit\Framework\TestCase;
use WSSC\WebSocketClient;

class WebSocketClientTest extends TestCase
{

    private const WS_SCHEME = 'ws://';
    private const WS_HOST   = 'localhost';
    private const WS_PORT   = ':8000';
    private const WS_URI    = '/notifications/messanger/vkjsndfvjn23243';

    private $url;

    public function setUp()/* The :void return type declaration that should be here would cause a BC issue */
    {
        $this->url = self::WS_SCHEME . self::WS_HOST . self::WS_PORT . self::WS_URI;
    }

    /**
     * @test
     */
    public function is_client_connected()
    {
        echo 'Running client...' . PHP_EOL;
        $recvMsg = '{"user_id" : 123}';
        $client = new WebSocketClient($this->url);
        $client->send($recvMsg);
        $recv = $client->receive();
        $this->assertEquals($recv, $recvMsg);
    }
}
