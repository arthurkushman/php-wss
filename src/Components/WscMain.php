<?php

namespace WSSC\Components;

use WSSC\Contracts\WscCommonsContract;
use WSSC\Exceptions\BadUriException;
use WSSC\Exceptions\ConnectionException;

class WscMain implements WscCommonsContract
{

    protected $socket, $is_connected = false, $is_closing = false, $last_opcode = NULL,
        $close_status = NULL, $huge_payload = NULL;
    protected $socketUrl = '';
    protected static $opcodes = [
        'continuation' => 0,
        'text'         => 1,
        'binary'       => 2,
        'close'        => 8,
        'ping'         => 9,
        'pong'         => 10,
    ];
    protected $options = [];

    protected function connect()
    {
        $urlParts = parse_url($this->socketUrl);
        $scheme = $urlParts['scheme'];
        $host = $urlParts['host'];
        $user = isset($urlParts['user']) ? $urlParts['user'] : '';
        $pass = isset($urlParts['pass']) ? $urlParts['pass'] : '';
        $port = isset($urlParts['port']) ? $urlParts['port'] : ($scheme === 'wss' ? 443 : 80);
        $path = isset($urlParts['path']) ? $urlParts['path'] : '/';
        $query = isset($urlParts['query']) ? $urlParts['query'] : '';
        $fragment = isset($urlParts['fragment']) ? $urlParts['fragment'] : '';

        $path_with_query = $path;
        if (!empty($query)) {
            $path_with_query .= '?' . $query;
        }
        if (!empty($fragment)) {
            $path_with_query .= '#' . $fragment;
        }

        if (!in_array($scheme, ['ws', 'wss'])) {
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

        $headers = [
            'Host'                  => $host . ':' . $port,
            'User-Agent'            => 'websocket-client-php',
            'Connection'            => 'Upgrade',
            'Upgrade'               => 'WebSocket',
            'Sec-WebSocket-Key'     => $key,
            'Sec-Websocket-Version' => '13',
        ];

        // Handle basic authentication.
        if ($user || $pass) {
            $headers['authorization'] = 'Basic ' . base64_encode($user . ':' . $pass) . "\r\n";
        }

        // Add and override with headers from options.
        if (isset($this->options['headers'])) {
            $headers = array_merge($headers, $this->options['headers']);
        }

        $header = 'GET ' . $path_with_query . " HTTP/1.1\r\n"
            . implode(
                "\r\n", array_map(
                    function ($key, $value) {
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
        $expectedResonse = base64_encode(pack('H*', sha1($key . self::SERVER_KEY_ACCEPT)));

        if ($keyAccept !== $expectedResonse) {
            throw new ConnectionException('Server sent bad upgrade response.');
        }

        $this->is_connected = true;
    }

    public function getLastOpcode()
    {
        return $this->last_opcode;
    }

    public function getCloseStatus()
    {
        return $this->close_status;
    }

    public function isConnected()
    {
        return $this->is_connected;
    }

    public function setTimeout($timeout)
    {
        $this->options['timeout'] = $timeout;

        if ($this->socket && get_resource_type($this->socket) === 'stream') {
            stream_set_timeout($this->socket, $timeout);
        }
    }

    public function setFragmentSize($fragment_size)
    {
        $this->options['fragment_size'] = $fragment_size;
        return $this;
    }

    public function getFragmentSize()
    {
        return $this->options['fragment_size'];
    }

    public function send($payload, $opcode = 'text', $masked = true)
    {
        if (!$this->is_connected) {
            $this->connect();
        }
        if (array_key_exists($opcode, self::$opcodes) === false) {
            throw new BadOpcodeException("Bad opcode '$opcode'.  Try 'text' or 'binary'.");
        }
        echo $payload;
        // record the length of the payload
        $payload_length = strlen($payload);

        $fragment_cursor = 0;
        // while we have data to send
        while ($payload_length > $fragment_cursor) {
            // get a fragment of the payload
            $sub_payload = substr($payload, $fragment_cursor, $this->options['fragment_size']);

            // advance the cursor
            $fragment_cursor += $this->options['fragment_size'];

            // is this the final fragment to send?
            $final = $payload_length <= $fragment_cursor;

            // send the fragment
            $this->send_fragment($final, $sub_payload, $opcode, $masked);

            // all fragments after the first will be marked a continuation
            $opcode = 'continuation';
        }
    }

    protected function send_fragment($final, $payload, $opcode, $masked)
    {
        // Binary string for header.
        $frameHeadBin = '';
        // Write FIN, final fragment bit.
        $frameHeadBin .= (bool)$final ? '1' : '0';
        // RSV 1, 2, & 3 false and unused.
        $frameHeadBin .= '000';
        // Opcode rest of the byte.
        $frameHeadBin .= sprintf('%04b', self::$opcodes[$opcode]);
        // Use masking?
        $frameHeadBin .= $masked ? '1' : '0';

        // 7 bits of payload length...
        $payloadLen = strlen($payload);
        if ($payloadLen > self::MAX_BYTES_READ) {
            $frameHeadBin .= decbin(self::MASK_127);
            $frameHeadBin .= sprintf('%064b', $payloadLen);
        } else if ($payloadLen > self::MASK_125) {
            $frameHeadBin .= decbin(self::MASK_126);
            $frameHeadBin .= sprintf('%016b', $payloadLen);
        } else {
            $frameHeadBin .= sprintf('%07b', $payloadLen);
        }

        $frame = '';

        // Write frame head to frame.
        foreach (str_split($frameHeadBin, 8) as $binstr) {
            $frame .= chr(bindec($binstr));
        }
        // Handle masking
        if ($masked) {
            // generate a random mask:
            $mask = '';
            for ($i = 0; $i < 4; $i++) {
                $mask .= chr(random_int(0, 255));
            }
            $frame .= $mask;
        }

        // Append payload to frame:
        for ($i = 0; $i < $payloadLen; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        $this->write($frame);
    }

    public function receive()
    {
        if (!$this->is_connected) {
            $this->connect();
        }
        $this->huge_payload = '';

        $response = NULL;
        while (NULL === $response) {
            $response = $this->receive_fragment();
        }
        return $response;
    }

    protected function receive_fragment()
    {
        // Just read the main fragment information first.
        $data = $this->read(2);

        // Is this the final fragment?  // Bit 0 in byte 0
        /// @todo Handle huge payloads with multiple fragments.
        $final = (boolean)(ord($data[0]) & 1 << 7);

        // Should be unused, and must be false…  // Bits 1, 2, & 3
        //      $rsv1  = (boolean) (ord($data[0]) & 1 << 6);
        //      $rsv2  = (boolean) (ord($data[0]) & 1 << 5);
        //      $rsv3  = (boolean) (ord($data[0]) & 1 << 4);
        // Parse opcode
        $opcode_int = ord($data[0]) & 31; // Bits 4-7
        $opcode_ints = array_flip(self::$opcodes);
        if (!array_key_exists($opcode_int, $opcode_ints)) {
            throw new ConnectionException("Bad opcode in websocket frame: $opcode_int");
        }
        $opcode = $opcode_ints[$opcode_int];

        // record the opcode if we are not receiving a continutation fragment
        if ($opcode !== 'continuation') {
            $this->last_opcode = $opcode;
        }

        // Masking?
        $mask = (boolean)(ord($data[1]) >> 7);  // Bit 0 in byte 1

        $payload = '';

        // Payload length
        $payload_length = (integer)ord($data[1]) & self::MASK_127; // Bits 1-7 in byte 1
        if ($payload_length > self::MASK_125) {
            if ($payload_length === self::MASK_126) {
                $data = $this->read(2); // 126: Payload is a 16-bit unsigned int
            } else {
                $data = $this->read(8); // 127: Payload is a 64-bit unsigned int
            }
            $payload_length = bindec(self::sprintB($data));
        }

        // Get masking key.
        if ($mask) {
            $masking_key = $this->read(4);
        }
        // Get the actual payload, if any (might not be for e.g. close frames.
        if ($payload_length > 0) {
            $data = $this->read($payload_length);

            if ($mask) {
                // Unmask payload.
                for ($i = 0; $i < $payload_length; $i++) {
                    $payload .= ($data[$i] ^ $masking_key[$i % 4]);
                }
            } else {
                $payload = $data;
            }
        }

        if ($opcode === 'close') {
            // Get the close status.
            if ($payload_length >= 2) {
                $status_bin = $payload[0] . $payload[1];
                $status = bindec(sprintf('%08b%08b', ord($payload[0]), ord($payload[1])));
                $this->close_status = $status;
                $payload = substr($payload, 2);

                if (!$this->is_closing) {
                    $this->send($status_bin . 'Close acknowledged: ' . $status, 'close'); // Respond.
                }
            }

            if ($this->is_closing) {
                $this->is_closing = false; // A close response, all done.
            }

            fclose($this->socket);
            $this->is_connected = false;
        }

        if (!$final) {
            $this->huge_payload .= $payload;
            return NULL;
        } // this is the last fragment, and we are processing a huge_payload

        if ($this->huge_payload) {
            $payload = $this->huge_payload .= $payload;
            $this->huge_payload = NULL;
        }

        return $payload;
    }

    /**
     * Tell the socket to close.
     *
     * @param integer $status http://tools.ietf.org/html/rfc6455#section-7.4
     * @param string $message A closing message, max 125 bytes.
     * @return bool|null|string
     */
    public function close($status = 1000, $message = 'ttfn')
    {
        $statusBin = sprintf('%016b', $status);
        $status_str = '';
        foreach (str_split($statusBin, 8) as $binstr) {
            $status_str .= chr(bindec($binstr));
        }
        $this->send($status_str . $message, 'close', true);
        $this->is_closing = true;
        return $this->receive(); // Receiving a close frame will close the socket now.
    }

    protected function write($data)
    {
        $written = fwrite($this->socket, $data);

        if ($written < strlen($data)) {
            throw new ConnectionException(
                "Could only write $written out of " . strlen($data) . " bytes."
            );
        }
    }

    protected function read($len)
    {
        $data = '';
        while (($dataLen = strlen($data)) < $len) {
            $buff = fread($this->socket, $len - $dataLen);
            if ($buff === false) {
                $metadata = stream_get_meta_data($this->socket);
                throw new ConnectionException(
                    'Broken frame, read ' . strlen($data) . ' of stated '
                    . $len . ' bytes.  Stream state: '
                    . json_encode($metadata)
                );
            }
            if ($buff === '') {
                $metadata = stream_get_meta_data($this->socket);
                throw new ConnectionException(
                    'Empty read; connection dead?  Stream state: ' . json_encode($metadata)
                );
            }
            $data .= $buff;
        }
        return $data;
    }

    /**
     * Helper to convert a binary to a string of '0' and '1'.
     */
    protected static function sprintB($string)
    {
        $return = '';
        $strLen = strlen($string);
        for ($i = 0; $i < $strLen; $i++) {
            $return .= sprintf('%08b', ord($string[$i]));
        }
        return $return;
    }

    /**
     * Sec-WebSocket-Key generator
     *
     * @return string   the 16 character length key
     */
    private function generateKey()
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"$&/()=[]{}0123456789';
        $key = '';
        $chLen = strlen($chars);
        for ($i = 0; $i < self::KEY_GEN_LENGTH; $i++) {
            $key .= $chars[random_int(0, $chLen - 1)];
        }
        return base64_encode($key);
    }

}
