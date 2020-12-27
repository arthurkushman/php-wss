<?php


namespace WSSCTEST;


use PHPUnit\Framework\TestCase;
use WSSC\Components\ClientConfig;
use WSSC\Exceptions\BadOpcodeException;
use WSSC\WebSocketClient;

class WebSocketSslClientTest extends TestCase
{
    private const WS_SCHEME = 'wss://';
    private const WS_HOST = 'localhost';
    private const WS_PORT = ':8888';
    private const WS_URI = '/notifications/messanger/vkjsndfvjn23243';

    private $url;

    public function setUp()/* The :void return type declaration that should be here would cause a BC issue */
    {
        $this->url = self::WS_SCHEME . self::WS_HOST . self::WS_PORT . self::WS_URI;
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_sends_msg_over_ssl()
    {
        $recvMsg = '{"user_id" : 123}';
        $client = new WebSocketClient($this->url, (new ClientConfig())->setContextOptions([
            'ssl' => [
                'allow_self_signed' => true,
                'verify_peer' => false,
//                'cafile' => './tests/certs/cert.pem',
                'local_cert' => './tests/certs/cert.pem',
            ]
        ]));
        try {
            $client->send($recvMsg);
        } catch (BadOpcodeException $e) {
            echo 'Couldn`t sent: ' . $e->getMessage();
        }
        $recv = $client->receive();
        $this->assertEquals($recv, $recvMsg);
    }
}