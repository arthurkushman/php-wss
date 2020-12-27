<?php


namespace WSSCTEST;


use PHPUnit\Framework\TestCase;
use WSSC\Components\ServerConfig;
use WSSC\WebSocketServer;

class WebSocketSslServerTest extends TestCase
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
        $config->setIsSsl(true)->setAllowSelfSigned(true)
            ->setCryptoType(STREAM_CRYPTO_METHOD_SSLv23_SERVER)
            ->setLocalCert("./tests/certs/cert.pem")->setLocalPk("./tests/certs/key.pem")
            ->setPort(8888);

        $websocketServer = new WebSocketServer(new ServerHandler(), $config);
        $websocketServer->run();
    }
}