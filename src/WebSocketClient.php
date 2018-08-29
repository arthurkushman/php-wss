<?php

namespace WSSC;

use WSSC\Components\WscMain;

class WebSocketClient extends WscMain
{

    /**
     * Sets parameters
     *
     * @param string $url string representation of a socket utf, ex.: tcp://www.example.com:8000 or udp://example.com:13
     * @param array $config ex.:
     * @throws \InvalidArgumentException
     * @throws Exceptions\BadUriException
     * @throws Exceptions\ConnectionException
     * @throws \Exception
     */
    public function __construct(string $url, array $config = [])
    {
        $this->socketUrl = $url;
        if (!array_key_exists('timeout', $config)) {
            $this->options['timeout'] = self::DEFAULT_TIMEOUT;
        } else {
            $this->options['timeout'] = $config['timeout'];
        }

        if (!array_key_exists('fragment_size', $config)) {
            $this->options['fragment_size'] = self::DEFAULT_FRAGMENT_SIZE;
        } else {
            $this->options['fragment_size'] = $config['fragment_size'];
        }

        if (array_key_exists('headers', $config)) {
            $this->options['headers'] = $config['headers'];
        }
        $this->connect();
    }
}
