<?php

namespace WSSC\Components;

use PHPUnit\Framework\OutputError;
use WSSC\Exceptions\ConnectionException;

class OriginComponent
{
    private $client;
    private $config;

    /**
     * OriginComponent constructor.
     * @param ServerConfig $config
     * @param $client
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
     */
    public function checkOrigin(string $headers): bool
    {
        preg_match('/Origin\:\s(.*?)\s/', $headers, $matches);
        if (empty($matches[1])) {
            $this->sendAndClose('No Origin header found.');
            return false;
        } else {
            $originHost = $matches[1];
            $allowedOrigins = $this->config->getOrigins();
            if (in_array($originHost, $allowedOrigins, true) === false) {
                $this->sendAndClose('Host ' . $originHost . ' is not allowed to pass access control as origin.');
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $msg
     * @throws \Exception
     */
    private function sendAndClose(string $msg)
    {
        $conn = new Connection($this->client);
        $conn->send($msg);
        $conn->close();
    }
}