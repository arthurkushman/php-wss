<?php

namespace WSSC\Components;

use WSSC\Contracts\WscCommonsContract;

class ClientConfig
{
    private $scheme;
    private $host;
    private $user;
    private $password;
    private $port;

    private $timeout = WscCommonsContract::DEFAULT_TIMEOUT;
    private $headers = [];
    private $fragmentSize = WscCommonsContract::DEFAULT_FRAGMENT_SIZE;
    private $context;


    private $hasProxy = false;
    private $proxyIp;
    private $proxyPort;
    private $proxyAuth;

    private $contextOptions = [];

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
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
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
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
     */
    public function setFragmentSize(int $fragmentSize)
    {
        $this->fragmentSize = $fragmentSize;
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
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return mixed
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @param void $scheme
     */
    public function setScheme($scheme): void
    {
        $this->scheme = $scheme;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param void $host
     */
    public function setHost($host): void
    {
        $this->host = $host;
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
     */
    public function setUser(array $urlParts): void
    {
        $this->user = isset($urlParts['user']) ? $urlParts['user'] : '';
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
     */
    public function setPassword(array $urlParts): void
    {
        $this->password = isset($urlParts['pass']) ? $urlParts['pass'] : '';
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
     */
    public function setPort(array $urlParts): void
    {
        $this->port = isset($urlParts['port']) ? $urlParts['port'] : ($this->scheme === 'wss' ? 443 : 80);
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
     */
    public function setContextOptions($contextOptions)
    {
        $this->contextOptions = $contextOptions;
    }

    public function setProxy($ip, $port, $username = '', $password = '')
    {
        $this->hasProxy  = true;
        $this->proxyIp   = $ip;
        $this->proxyPort = $port;
        $this->proxyAuth = ($username && $password) ? base64_encode($username.':'.$password) : null;
    }

    public function hasProxy() : bool
    {
        return $this->hasProxy;
    }

    public function getProxyIp() : ?string
    {
        return ($this->hasProxy) ? $this->proxyIp : null;
    }

    public function getProxyPort() : ?string
    {
        return ($this->hasProxy) ? $this->proxyPort : null;
    }

    public function getProxyAuth() : ?string
    {
        return ($this->hasProxy) ? $this->proxyAuth : null;
    }

}
