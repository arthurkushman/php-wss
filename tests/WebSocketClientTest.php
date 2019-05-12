<?php

namespace WSSCTEST;

use PHPUnit\Framework\TestCase;
use WSSC\Components\ClientConfig;
use WSSC\Exceptions\BadOpcodeException;
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
     * @throws \Exception
     */
    public function is_client_connected()
    {
        $recvMsg = '{"user_id" : 123}';
        $client = new WebSocketClient($this->url, new ClientConfig());
        try {
            $client->send($recvMsg);
        } catch (BadOpcodeException $e) {
            echo 'Couldn`t sent: ' . $e->getMessage();
        }
        $recv = $client->receive();
        $this->assertEquals($recv, $recvMsg);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_sends_with_headers_via_constructor()
    {
        $config = new ClientConfig();
        $config->setFragmentSize(8096);
        $config->setTimeout(15);
        $config->setHeaders([
            'X-Custom-Header' => 'Foo Bar Baz',
        ]);

        $recvMsg = '{"user_id" : 123}';
        $client = new WebSocketClient($this->url, $config);

        try {
            $client->send($recvMsg);
        } catch (BadOpcodeException $e) {
            echo 'Couldn`t sent: ' . $e->getMessage();
        }

        $recv = $client->receive();
        $this->assertEquals($recv, $recvMsg);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_closes_connection()
    {
        $client = new WebSocketClient($this->url, new ClientConfig());

        $closeRecv = $client->close();
        $this->assertEmpty($closeRecv);
    }
}
