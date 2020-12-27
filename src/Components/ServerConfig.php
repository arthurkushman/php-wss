<?php

namespace WSSC\Components;

use WSSC\Contracts\WebSocketServerContract;

class ServerConfig
{
    /**
     * @var int
     */
    private $clientsPerFork = WebSocketServerContract::CLIENTS_PER_FORK;

    /**
     * @var int
     */
    private $streamSelectTimeout = WebSocketServerContract::STREAM_SELECT_TIMEOUT;

    /**
     * @var string
     */
    private $host = WebSocketServerContract::DEFAULT_HOST;

    /**
     * @var int
     */
    private $port = WebSocketServerContract::DEFAULT_PORT;

    /**
     * @var bool
     */
    private $isForking = true;

    /**
     * @var string
     */
    private $processName = WebSocketServerContract::PROC_TITLE;

    /**
     * @var bool
     */
    private $checkOrigin = false;

    /**
     * @var array
     */
    private $origins = [];


    /**
     * @var bool
     */
    private $originHeader = false;

    /**
     * @var bool
     */
    private $isSsl = false;

    /**
     * @var string
     */
    private $localCert;

    /**
     * @var string
     */
    private $localPk;

    /**
     * @var bool
     */
    private $allowSelfSigned;

    /**
     * @var int
     */
    private $cryptoType;

    /**
     * @return mixed
     */
    public function getClientsPerFork(): int
    {
        return $this->clientsPerFork;
    }

    /**
     * @param mixed $clientsPerFork
     * @return ServerConfig
     */
    public function setClientsPerFork(int $clientsPerFork): ServerConfig
    {
        $this->clientsPerFork = $clientsPerFork;
        return $this;
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
     * @return ServerConfig
     */
    public function setStreamSelectTimeout(int $streamSelectTimeout): ServerConfig
    {
        $this->streamSelectTimeout = $streamSelectTimeout;
        return $this;
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
     * @return ServerConfig
     */
    public function setHost(string $host): ServerConfig
    {
        $this->host = $host;
        return $this;
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
     * @return ServerConfig
     */
    public function setPort(int $port): ServerConfig
    {
        $this->port = $port;
        return $this;
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
    public function setForking(bool $isForking): ServerConfig
    {
        $this->isForking = $isForking;
        return $this;
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
    public function setProcessName(string $processName): ServerConfig
    {
        $this->processName = $processName;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCheckOrigin(): bool
    {
        return $this->checkOrigin;
    }

    /**
     * @param bool $checkOrigin
     * @return ServerConfig
     */
    public function setCheckOrigin(bool $checkOrigin): ServerConfig
    {
        $this->checkOrigin = $checkOrigin;
        return $this;
    }

    /**
     * @return array
     */
    public function getOrigins(): array
    {
        return $this->origins;
    }

    /**
     * @param array $origins
     * @return ServerConfig
     */
    public function setOrigins(array $origins): ServerConfig
    {
        if (empty($origins) === false) {
            $this->setCheckOrigin(true);
        }
        $this->origins = $origins;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOriginHeader(): bool
    {
        return $this->originHeader;
    }

    /**
     * @param bool $originHeader
     * @return ServerConfig
     */
    public function setOriginHeader(bool $originHeader): ServerConfig
    {
        $this->originHeader = $originHeader;
        return $this;
    }

    /**
     * @param bool $isSsl
     * @return ServerConfig
     */
    public function setIsSsl(bool $isSsl): ServerConfig
    {
        $this->isSsl = $isSsl;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSsl(): bool
    {
        return $this->isSsl;
    }

    /**
     * @param mixed $localCert
     * @return ServerConfig
     */
    public function setLocalCert(string $localCert): ServerConfig
    {
        $this->localCert = $localCert;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocalCert(): string
    {
        return $this->localCert;
    }

    /**
     * @param mixed $localPk
     * @return ServerConfig
     */
    public function setLocalPk(string $localPk): ServerConfig
    {
        $this->localPk = $localPk;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocalPk(): string
    {
        return $this->localPk;
    }

    /**
     * @param bool $allowSelfSigned
     * @return ServerConfig
     */
    public function setAllowSelfSigned(bool $allowSelfSigned): ServerConfig
    {
        $this->allowSelfSigned = $allowSelfSigned;
        return $this;
    }

    /**
     * @return bool
     */
    public function getAllowSelfSigned(): bool
    {
        return $this->allowSelfSigned;
    }

    /**
     * @param int $cryptoType
     * @return ServerConfig
     */
    public function setCryptoType(int $cryptoType): ServerConfig
    {
        $this->cryptoType = $cryptoType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCryptoType(): int
    {
        return $this->cryptoType;
    }
}