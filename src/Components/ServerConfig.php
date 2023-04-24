<?php

namespace WSSC\Components;

use Exception;
use WSSC\Contracts\WebSocketServerContract;

class ServerConfig
{
    /**
     * @var int
     */
    private int $clientsPerFork = WebSocketServerContract::CLIENTS_PER_FORK;

    /**
     * @var int|null
     */
    private ?int $streamSelectTimeout = WebSocketServerContract::STREAM_SELECT_TIMEOUT;

    /**
     * @var string
     */
    private string $host = WebSocketServerContract::DEFAULT_HOST;

    /**
     * @var int
     */
    private int $port = WebSocketServerContract::DEFAULT_PORT;

    /**
     * @var bool
     */
    private bool $isForking = true;

    /**
     * @var string
     */
    private string $processName = WebSocketServerContract::PROC_TITLE;

    /**
     * @var bool
     */
    private bool $checkOrigin = false;

    /**
     * @var array
     */
    private array $origins = [];

    /**
     * @var bool
     */
    private bool $originHeader = false;

    /**
     * @var bool
     */
    private bool $isSsl = false;

    /**
     * @var string
     */
    private string $localCert;

    /**
     * @var string
     */
    private string $localPk;

    /**
     * @var bool
     */
    private bool $allowSelfSigned;

    /**
     * @var int
     */
    private int $cryptoType;

    /**
     * @var int
     */
    private int $loopingDelay = 0;

    /**
     * @var array
     */
    private array $loopingDelayRange = [0, 1000];

    /**
     * @return int
     */
    public function getClientsPerFork(): int
    {
        return $this->clientsPerFork;
    }

    /**
     * @param int $clientsPerFork
     * @return ServerConfig
     */
    public function setClientsPerFork(int $clientsPerFork): ServerConfig
    {
        $this->clientsPerFork = $clientsPerFork;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getStreamSelectTimeout(): ?int
    {
        return $this->streamSelectTimeout;
    }

    /**
     * @param null|int $streamSelectTimeout
     * @return ServerConfig
     */
    public function setStreamSelectTimeout(?int $streamSelectTimeout): ServerConfig
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
     * @return ServerConfig
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

    /**
     * Set the looping sleep in milliseconds
     *
     * @return self
     */
    public function setLoopingDelay(int $loopingDelay): self
    {
        if (($loopingDelay < $this->loopingDelayRange[0]) || ($loopingDelay > $this->loopingDelayRange[1])) {
            throw new Exception('loopingDelay value must be between 1 and 1000 milliseconds.');
        }

        $this->loopingDelay = $loopingDelay;

        return $this;
    }

    /**
     * @return int
     */
    public function getLoopingDelay(): int
    {
        return $this->loopingDelay;
    }
}
