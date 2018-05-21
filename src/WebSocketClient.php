<?php

namespace WSSC;

use WSSC\Components\WscMain;

/**
 * Class WebSocketClient
 * @package WSSC
 */
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
        if (!isset($config['timeout'])) {
            $this->options['timeout'] = self::DEFAULT_TIMEOUT;
        }
        if (!isset($config['fragment_size'])) {
            $this->options['fragment_size'] = self::DEFAULT_FRAGMENT_SIZE;
        }
        if (isset($config['headers']) && \is_array($config['headers'])) {
            $this->options['headers'] = $config['headers'];
        }
    }
}
