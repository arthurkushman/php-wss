<?php

namespace WSSCTEST;

use PHPUnit\Framework\TestCase;
use WSSC\Components\ClientConfig;
use WSSC\Exceptions\BadOpcodeException;
use WSSC\WebSocketClient;

class WebSocketClientTest extends TestCase
{
    private const WS_SCHEME = 'ws://';
    private const WS_HOST = 'localhost';
    private const WS_PORT = ':8000';
    private const WS_URI = '/notifications/messanger/vkjsndfvjn23243';

    private string $url = self::WS_SCHEME . self::WS_HOST . self::WS_PORT . self::WS_URI;

    public function setUp(): void
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
    public function can_send_larg_payload()
    {
        $recvMsg = '{
  "squadName": "Super hero squad",
  "homeTown": "Metro City",
  "formed": 2016,
  "secretBase": "Super tower",
  "active": true,
  "members": [
    {
      "name": "Molecule Man",
      "age": 29,
      "secretIdentity": "Dan Jukes",
      "powers": [
        "Radiation resistance",
        "Turning tiny",
        "Radiation blast"
      ]
    },
    {
      "name": "Madame Uppercut",
      "age": 39,
      "secretIdentity": "Jane Wilson",
      "powers": [
        "Million tonne punch",
        "Damage resistance",
        "Superhuman reflexes"
      ]
    },
    {
      "name": "Eternal Flame",
      "age": 1000000,
      "secretIdentity": "Unknown",
      "powers": [
        "Immortality",
        "Heat Immunity",
        "Inferno",
        "Teleportation",
        "Interdimensional travel"
      ]
    },
    {
      "name": "Madame Uppercut",
      "age": 39,
      "secretIdentity": "Jane Wilson",
      "powers": [
        "Million tonne punch",
        "Damage resistance",
        "Superhuman reflexes"
      ]
    },
    {
      "name": "Eternal Flame",
      "age": 1000000,
      "secretIdentity": "Unknown",
      "powers": [
        "Immortality",
        "Heat Immunity",
        "Inferno",
        "Teleportation",
        "Interdimensional travel"
      ]
    },   
    {
      "name": "Madame Uppercut",
      "age": 39,
      "secretIdentity": "Jane Wilson",
      "powers": [
        "Million tonne punch",
        "Damage resistance",
        "Superhuman reflexes"
      ]
    },
    {
      "name": "Eternal Flame",
      "age": 1000000,
      "secretIdentity": "Unknown",
      "powers": [
        "Immortality",
        "Heat Immunity",
        "Inferno",
        "Teleportation",
        "Interdimensional travel"
      ]
    },     
    {
      "name": "Madame Uppercut",
      "age": 39,
      "secretIdentity": "Jane Wilson",
      "powers": [
        "Million tonne punch",
        "Damage resistance",
        "Superhuman reflexes"
      ]
    },
    {
      "name": "Eternal Flame",
      "age": 1000000,
      "secretIdentity": "Unknown",
      "powers": [
        "Immortality",
        "Heat Immunity",
        "Inferno",
        "Teleportation",
        "Interdimensional travel"
      ]
    },     
    {
      "name": "Madame Uppercut",
      "age": 39,
      "secretIdentity": "Jane Wilson",
      "powers": [
        "Million tonne punch",
        "Damage resistance",
        "Superhuman reflexes"
      ]
    },
    {
      "name": "Eternal Flame",
      "age": 1000000,
      "secretIdentity": "Unknown",
      "powers": [
        "Immortality",
        "Heat Immunity",
        "Inferno",
        "Teleportation",
        "Interdimensional travel"
      ]
    },     
    {
      "name": "Madame Uppercut",
      "age": 39,
      "secretIdentity": "Jane Wilson",
      "powers": [
        "Million tonne punch",
        "Damage resistance",
        "Superhuman reflexes"
      ]
    },
    {
      "name": "Eternal Flame",
      "age": 1000000,
      "secretIdentity": "Unknown",
      "powers": [
        "Immortality",
        "Heat Immunity",
        "Inferno",
        "Teleportation",
        "Interdimensional travel"
      ]
    }    
  ]
}';

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
//            'Origin' => 'example.com'
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
