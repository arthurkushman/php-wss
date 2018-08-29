<?php

namespace WSSCTEST;

use PHPUnit\Framework\TestCase;
use WSSC\Exceptions\BadOpcodeException;
use WSSC\WebSocketClient;

class WebSocketClientTest extends TestCase
{

    const WS_SCHEME = 'ws://';
    const WS_HOST   = 'localhost';
    const WS_PORT   = ':8000';
    const WS_URI    = '/notifications/messanger/vkjsndfvjn23243';

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
        $client = new WebSocketClient($this->url);
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
        $recvMsg = '{"user_id" : 123}';
        $client = new WebSocketClient($this->url, [
            'timeout'       => 15,
            'fragment_size' => 8096,
            'headers'       => [
                'X-Custom-Header' => 'Foo Bar Baz',
            ],
        ]);

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
    public function it_sends_with_headers_via_setters()
    {
        $recvMsg = '{"user_id" : 123}';
        $client = new WebSocketClient($this->url);
        $client->setFragmentSize(8096)->setTimeout(15)->setHeaders([
            'X-Custom-Header' => 'Foo Bar Baz'
        ]);

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
        $client = new WebSocketClient($this->url);

        $closeRecv = $client->close();
        $this->assertEmpty($closeRecv);
    }
}
