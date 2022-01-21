<?php

namespace WSSCTEST\Benchmark;

require_once __DIR__ . '/../../vendor/autoload.php';

use Exception;
use WSSC\Components\ClientConfig;
use WSSC\WebSocketClient;

class MaxConnectionsBench
{
    private const WS_SCHEME = 'ws://';
    private const WS_HOST = 'localhost';
    private const WS_PORT = ':8000';
    private const WS_URI = '/notifications/messanger/vkjsndfvjn23243';

    private string $url = self::WS_SCHEME . self::WS_HOST . self::WS_PORT . self::WS_URI;
    private WebSocketClient $client;

    /**
     * @throws \WSSC\Exceptions\BadUriException
     * @throws \WSSC\Exceptions\ConnectionException
     */
    public function __construct()
    {
        $this->client = new WebSocketClient($this->url, new ClientConfig());
    }

    /**
     * @Revs(10000)
     * @Iterations(3)
     * @throws Exception
     */
    public function benchConnect(): void
    {
        $this->client->send('{"user_id" : 123}');
    }
}
