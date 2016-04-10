<?php

namespace WSSC;

class WebSocketClient {

    const DEFAULT_TIMEOUT = 5,
            DEFAULT_FRAGMENT_SIZE = 4096, 
            DEFAULT_RESPONSE_HEADER = 1024;

    const SEC_WEBSOCKET_ACCEPT_PTTRN = '/Sec-WebSocket-Accept:\s(.*)$/mUi';
    
    private $socketUrl = '',
            $socketOptions = [];

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

    protected function connect() {
        $urlParts = parse_url($this->socketUrl);
        $scheme = $url_parts['scheme'];
        $host = $url_parts['host'];
        $user = isset($url_parts['user']) ? $url_parts['user'] : '';
        $pass = isset($url_parts['pass']) ? $url_parts['pass'] : '';
        $port = isset($url_parts['port']) ? $url_parts['port'] : ($scheme === 'wss' ? 443 : 80);
        $path = isset($url_parts['path']) ? $url_parts['path'] : '/';
        $query = isset($url_parts['query']) ? $url_parts['query'] : '';
        $fragment = isset($url_parts['fragment']) ? $url_parts['fragment'] : '';

        $path_with_query = $path;
        if (!empty($query))
            $path_with_query .= '?' . $query;
        if (!empty($fragment))
            $path_with_query .= '#' . $fragment;

        if (!in_array($scheme, array('ws', 'wss'))) {
            throw new BadUriException(
            "Url should have scheme ws or wss, not '$scheme' from URI '$this->socket_uri' ."
            );
        }

        $host_uri = ($scheme === 'wss' ? 'ssl' : 'tcp') . '://' . $host;

        // Set the stream context options if they're already set in the config
        if (isset($this->options['context'])) {
            // Suppress the error since we'll catch it below
            if (@get_resource_type($this->options['context']) === 'stream-context') {
                $context = $this->options['context'];
            } else {
                throw new \InvalidArgumentException(
                "Stream context in \$options['context'] isn't a valid context"
                );
            }
        } else {
            $context = stream_context_create();
        }

        $this->socket = @stream_socket_client(
                        $host_uri . ':' . $port, $errno, $errstr, $this->options['timeout'], STREAM_CLIENT_CONNECT, $context
        );

        if ($this->socket === false) {
            throw new ConnectionException(
            "Could not open socket to \"$host:$port\": $errstr ($errno)."
            );
        }

        // Set timeout on the stream as well.
        stream_set_timeout($this->socket, $this->options['timeout']);

        // Generate the WebSocket key.
        $key = $this->generateKey();

        $headers = array(
            'Host' => $host . ":" . $port,
            'User-Agent' => 'websocket-client-php',
            'Connection' => 'Upgrade',
            'Upgrade' => 'WebSocket',
            'Sec-Websocket-Key' => $key,
            'sec-websocket-version' => '13',
        );

        // Handle basic authentication.
        if ($user || $pass) {
            $headers['authorization'] = 'Basic ' . base64_encode($user . ':' . $pass) . "\r\n";
        }

        // Add and override with headers from options.
        if (isset($this->options['headers'])) {
            $headers = array_merge($headers, $this->options['headers']);
        }

        $header = "GET " . $path_with_query . " HTTP/1.1\r\n"
                . implode(
                        "\r\n", array_map(
                                function($key, $value) {
                            return "$key: $value";
                        }, array_keys($headers), $headers
                        )
                )
                . "\r\n\r\n";

        // Send headers.
        $this->write($header);

        // Get server response header 
        $response = stream_get_line($this->socket, self::DEFAULT_RESPONSE_HEADER, "\r\n\r\n"); 

        /// @todo Handle version switching
        // Validate response.
        if (!preg_match(self::SEC_WEBSOCKET_ACCEPT_PTTRN, $response, $matches)) {
            $address = $scheme . '://' . $host . $path_with_query;
            throw new ConnectionException(
            "Connection to '{$address}' failed: Server sent invalid upgrade response:\n"
            . $response
            );
        }

        $keyAccept = trim($matches[1]);
        $expectedResonse = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

        if ($keyAccept !== $expectedResonse) {
            throw new ConnectionException('Server sent bad upgrade response.');
        }

        $this->is_connected = true;
    }

    /**
     * Sec-WebSocket-Key generator
     * @return string   the 16 character length key
     */
    private function generateKey() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"$&/()=[]{}0123456789';
        $key = '';
        $chLen = strlen($chars);
        for ($i = 0; $i < 16; $i++) {
            $key .= $chars[mt_rand(0, $chLen - 1)];
        }
        return base64_encode($key);
    }

}
