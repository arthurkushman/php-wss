<?php

namespace WSSC\Components;

use WSSC\Contracts\WscCommonsContract;

/**
 * Class ClientConfig
 * @package WSSC\Components
 */
class ClientConfig
{
    /**
     * @var string
     */
    private string $scheme;

    /**
     * @var string
     */
    private string $host;

    /**
     * @var string
     */
    private string $user;

    /**
     * @var string
     */
    private string $password;

    /**
     * @var string
     */
    private string $port;

    /**
     * @var int
     */
    private int $timeout = WscCommonsContract::DEFAULT_TIMEOUT;

    /**
     * @var array
     */
    private array $headers = [];

    /**
     * @var int
     */
    private int $fragmentSize = WscCommonsContract::DEFAULT_FRAGMENT_SIZE;

    /**
     * @var null|resource
     */
    private $context;

    /**
     * @var bool
     */
    private bool $hasProxy = false;

    /**
     * @var string
     */
    private string $proxyIp;

    /**
     * @var string
     */
    private string $proxyPort;

    /**
     * @var string|null
     */
    private ?string $proxyAuth;

    /**
     * @var array
     */
    private array $contextOptions = [];

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     * @return ClientConfig
     */
    public function setTimeout(int $timeout): ClientConfig
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @return ClientConfig
     */
    public function setHeaders(array $headers): ClientConfig
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @return int
     */
    public function getFragmentSize(): int
    {
        return $this->fragmentSize;
    }

    /**
     * @param int $fragmentSize
     * @return ClientConfig
     */
    public function setFragmentSize(int $fragmentSize): ClientConfig
    {
        $this->fragmentSize = $fragmentSize;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     * @return ClientConfig
     */
    public function setContext($context): ClientConfig
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @param string $scheme
     * @return ClientConfig
     */
    public function setScheme(string $scheme): ClientConfig
    {
        $this->scheme = $scheme;
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
     * @return ClientConfig
     */
    public function setHost(string $host): ClientConfig
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param array $urlParts
     * @return ClientConfig
     */
    public function setUser(array $urlParts): ClientConfig
    {
        $this->user = $urlParts['user'] ?? '';
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param array $urlParts
     * @return ClientConfig
     */
    public function setPassword(array $urlParts): ClientConfig
    {
        $this->password = $urlParts['pass'] ?? '';
        return $this;
    }

    /**
     * @return string
     */
    public function getPort(): string
    {
        return $this->port;
    }

    /**
     * @param array $urlParts
     * @return ClientConfig
     */
    public function setPort(array $urlParts): ClientConfig
    {
        $this->port = $urlParts['port'] ?? ($this->scheme === 'wss' ? '443' : '80');
        return $this;
    }

    /**
     * @return array
     */
    public function getContextOptions(): array
    {
        return $this->contextOptions;
    }

    /**
     * @param array $contextOptions
     * @return ClientConfig
     */
    public function setContextOptions(array $contextOptions): ClientConfig
    {
        $this->contextOptions = $contextOptions;
        return $this;
    }

    /**
     * @param string $ip
     * @param string $port
     * @return ClientConfig
     */
    public function setProxy(string $ip, string $port): ClientConfig
    {
        $this->hasProxy  = true;
        $this->proxyIp   = $ip;
        $this->proxyPort = $port;

        return $this;
    }

    /**
     * Sets auth for proxy
     *
     * @param string $userName
     * @param string $password
     */
    public function setProxyAuth(string $userName, string $password): ClientConfig
    {
        $this->proxyAuth = (empty($userName) === false && empty($password) === false) ? base64_encode($userName.':'.$password) : null;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasProxy() : bool
    {
        return $this->hasProxy;
    }

    /**
     * @return string|null
     */
    public function getProxyIp() : ?string
    {
        return $this->proxyIp;
    }

    /**
     * @return string|null
     */
    public function getProxyPort() : ?string
    {
        return $this->proxyPort;
    }

    /**
     * @return string|null
     */
    public function getProxyAuth() : ?string
    {
        return $this->proxyAuth;
    }
}
