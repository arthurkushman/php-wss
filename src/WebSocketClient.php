<?php

namespace WSSC;

class WebSocketClient extends WscMain {

    private $socketOptions = [];   
    /**
     * Sets parameters
     * @param string $url   string representation of a socket utf, ex.: tcp://www.example.com:8000 or udp://example.com:13
     * @param array $config ex.: 
     */
    public function __construct($url, $config) {
        $this->socketUrl = $url;
        $this->socketOptions = $config;
        if (!array_key_exists('timeout', $this->socketOptions)) {
            $this->options['timeout'] = self::DEFAULT_TIMEOUT;
        }
        if (!array_key_exists('fragment_size', $this->socketOptions)) {
            $this->options['fragment_size'] = self::DEFAULT_FRAGMENT_SIZE;
        }
    }

}
