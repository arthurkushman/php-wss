<?php

namespace WSSC;

use WSSC\Components\WscMain;

class WebSocketClient extends WscMain
{

    /**
     * Sets parameters
     *
     * @param string $url   string representation of a socket utf, ex.: tcp://www.example.com:8000 or udp://example.com:13
     * @param array $config ex.:
     */
    public function __construct(string $url, array $config = [])
    {
        $this->socketUrl = $url;
        if (!array_key_exists('timeout', $config)) {
            $this->options['timeout'] = self::DEFAULT_TIMEOUT;
        }
        if (!array_key_exists('fragment_size', $config)) {
            $this->options['fragment_size'] = self::DEFAULT_FRAGMENT_SIZE;
        }
        $this->connect();
    }
}
