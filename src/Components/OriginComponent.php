<?php

namespace WSSC\Components;

/**
 * Class OriginComponent
 * @package WSSC\Components
 */
class OriginComponent
{
    /**
     * @var false|resource
     */
    private $client;


    /**
     * @var ServerConfig
     */
    private ServerConfig $config;

    /**
     * OriginComponent constructor.
     * @param ServerConfig $config
     * @param false|resource $client
     */
    public function __construct(ServerConfig $config, $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * Checks if there is a compatible origin header came from client
     * @param string $headers
     * @return bool
     * @throws \Exception
     */
    public function checkOrigin(string $headers): bool
    {
        preg_match('/Origin\:\s(.*?)\s/', $headers, $matches);
        if (empty($matches[1])) {
            $this->sendAndClose('No Origin header found.');
            return false;
        }

        $originHost = $matches[1];
        $allowedOrigins = $this->config->getOrigins();
        if (in_array($originHost, $allowedOrigins, true) === false) {
            $this->sendAndClose('Host ' . $originHost . ' is not allowed to pass access control as origin.');
            return false;
        }

        return true;
    }

    /**
     * @param string $msg
     * @throws \Exception
     */
    private function sendAndClose(string $msg): void
    {
        $conn = new Connection($this->client);
        $conn->send($msg);
        $conn->close();
    }
}