<?php

namespace WSSC\Components;

use WSSC\Contracts\WebSocketServerContract;

class ServerConfig
{

    private $clientsPerFork = WebSocketServerContract::CLIENTS_PER_FORK;
    private $streamSelectTimeout = WebSocketServerContract::STREAM_SELECT_TIMEOUT;

    private $host = WebSocketServerContract::DEFAULT_HOST;
    private $port = WebSocketServerContract::DEFAULT_PORT;

    /**
     * @return mixed
     */
    public function getClientsPerFork(): int
    {
        return $this->clientsPerFork;
    }

    /**
     * @param mixed $clientsPerFork
     */
    public function setClientsPerFork(int $clientsPerFork)
    {
        $this->clientsPerFork = $clientsPerFork;
    }

    /**
     * @return mixed
     */
    public function getStreamSelectTimeout(): int
    {
        return $this->streamSelectTimeout;
    }

    /**
     * @param mixed $streamSelectTimeout
     */
    public function setStreamSelectTimeout(int $streamSelectTimeout)
    {
        $this->streamSelectTimeout = $streamSelectTimeout;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host)
    {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port)
    {
        $this->port = $port;
    }
}