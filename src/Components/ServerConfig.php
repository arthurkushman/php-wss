<?php

namespace WSSC\Components;

use WSSC\Contracts\WebSocketServerContract;

class ServerConfig
{

    private $clientsPerFork = WebSocketServerContract::CLIENTS_PER_FORK;
    private $streamSelectTimeout = WebSocketServerContract::STREAM_SELECT_TIMEOUT;

    private $host = WebSocketServerContract::DEFAULT_HOST;
    private $port = WebSocketServerContract::DEFAULT_PORT;

    private $isForking = true;

    private $processName = WebSocketServerContract::PROC_TITLE;

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
    public function setClientsPerFork(int $clientsPerFork): void
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
    public function setStreamSelectTimeout(int $streamSelectTimeout): void
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
    public function setHost(string $host): void
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
    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    /**
     * @return bool
     */
    public function isForking(): bool
    {
        return $this->isForking;
    }

    /**
     * @param bool $isForking
     */
    public function setForking(bool $isForking): void
    {
        $this->isForking = $isForking;
    }

    /**
     * @return string
     */
    public function getProcessName(): string
    {
        return $this->processName;
    }

    /**
     * @param string $processName
     */
    public function setProcessName(string $processName): void
    {
        $this->processName = $processName;
    }
}