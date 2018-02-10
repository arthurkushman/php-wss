<?php

namespace WSSC;

use WSSC\Components\Connection;
use WSSC\Contracts\CommonsContract;
use WSSC\Contracts\WebSocket;
use WSSC\Contracts\WebSocketServerContract;
use WSSC\Exceptions\WebSocketException;

/**
 * Create by Arthur Kushman
 */
class WebSocketServer implements WebSocketServerContract, CommonsContract
{

    private $clients = [];
    // set any template You need ex.: GET /subscription/messenger/token
    private $pathParams = [];
    private $config;
    private $handshakes = [];
    private $headersUpgrade = [];
    private $totalClients = 0;
    private $maxClients = 1;
    private $handler;
    private $connImpl;
    private $cureentConn;
    // for the very 1st time must be true
    private $stepRecursion = true;

    const MAX_BYTES_READ = 8192;
    const HEADER_BYTES_READ = 1024;
    // must be the time for interaction between each client
    const STREAM_SELECT_TIMEOUT = 3600;
    // stream non-blocking 
    const NON_BLOCK = 0;
    // max clients to fork another process
    const MAX_CLIENTS_REMAINDER_FORK = 1000;
    const PROC_TITLE = 'php-wss';

    /**
     * WebSocketServer constructor.
     * @param WebSocket $handler
     * @param array $config
     */
    public function __construct(
        WebSocket $handler,
        $config = [
            'host' => self::DEFAULT_HOST,
            'port' => self::DEFAULT_PORT,
        ]
    )
    {
        ini_set('default_socket_timeout', 5); // this should be >= 5 sec, otherwise there will be broken pipe - tested
        $this->handler = $handler;
        $this->config = $config;
        $this->connImpl = new Connection();
    }

    /**
     * Runs main process - Anscestor with server socket on TCP
     */
    public function run()
    {
        $errno = NULL;
        $errorMessage = '';

        $server = stream_socket_server("tcp://{$this->config['host']}:{$this->config['port']}", $errno, $errorMessage);
        if ($server === false) {
            die('Could not bind to socket: ' . $errno . ' - ' . $errorMessage . PHP_EOL);
        }
        @cli_set_process_title(self::PROC_TITLE);
        $this->eventLoop($server);
    }

    /**
     * Recursive event loop that input intu recusion by remainder = 0 - thus when N users,
     * and when forks equals true which prevents it from infinite recursive iterations
     *
     * @param resource $server server connection
     * @param bool $fork flag to fork or run event loop
     */
    private function eventLoop($server, bool $fork = false)
    {
        if ($fork === true) {
            $pid = pcntl_fork();

            if ($pid) { // run eventLoop in parent        
                @cli_set_process_title(self::PROC_TITLE);
                $this->eventLoop($server);
            }
        } else {
            $this->looping($server);
        }
    }

    /**
     * @param resource $server
     */
    private function looping($server)
    {
        while (true) {
            $this->totalClients = count($this->clients) + 1;

            // maxClients prevents process fork on count down
            if ($this->totalClients > $this->maxClients) {
                $this->maxClients = $this->totalClients;
            }

            if ($this->totalClients !== 0 // avoid 0 process creation
                && $this->totalClients % self::MAX_CLIENTS_REMAINDER_FORK === 0 // only when N is there
                && true === $this->stepRecursion // only once
                && $this->maxClients === $this->totalClients // only if stack grows
            ) {
                $this->stepRecursion = false;
                $this->eventLoop($server, true);
            }

            if ($this->totalClients !== 0 && $this->totalClients % self::MAX_CLIENTS_REMAINDER_FORK === 0 && $this->maxClients > $this->totalClients) { // there is less connection for amount of processes at this moment
                exit(1);
            }

            //prepare readable sockets
            $readSocks = $this->clients;
            $readSocks[] = $server;

            //start reading and use a large timeout
            if (!stream_select($readSocks, $write, $except, self::STREAM_SELECT_TIMEOUT)) {
                die('something went wrong while selecting');
            }

            //new client
            if (in_array($server, $readSocks, false)) {
                $this->acceptNewClient($server, $readSocks);
            }

            //message from existing client
            $this->messagesWorker($readSocks);
        }
    }

    /**
     * @param resource $server
     * @param array $readSocks
     */
    private function acceptNewClient($server, array &$readSocks) : void
    {
        $newClient = stream_socket_accept($server, 0); // must be 0 to non-block
        if ($newClient) {
            // print remote client information, ip and port number
//            $socketName = stream_socket_get_name($newClient, true);
            // important to read from headers here coz later client will change and there will be only msgs on pipe
            $headers = fread($newClient, self::HEADER_BYTES_READ);
            if (empty($this->handler->pathParams[0]) === false) {
                $this->setPathParams($headers);
            }
            $this->clients[] = $newClient;
            $this->stepRecursion = true; // set on new client coz of remainder % is always 0
            // trigger OPEN event
            $this->handler->onOpen($this->connImpl->getConnection($newClient));
            $this->handshake($newClient, $headers);
        }
        //delete the server socket from the read sockets
        unset($readSocks[array_search($server, $readSocks, false)]);
    }

    /**
     * @uses onMessage
     * @uses onPing
     * @uses onPong
     * @param array $readSocks
     */
    private function messagesWorker(array $readSocks) : void
    {
        foreach ($readSocks as $kSock => $sock) {
            $data = $this->decode(fread($sock, self::MAX_BYTES_READ));
            $dataType = $data['type'];
            $dataPayload = $data['payload'];
            // to manipulate connection through send/close methods via handler, specified in IConnection
            $this->cureentConn = $this->connImpl->getConnection($sock);
            if (empty($data) || $dataType === self::EVENT_TYPE_CLOSE) { // close event triggered from client - browser tab or close socket event
                // trigger CLOSE event
                try {
                    $this->handler->onClose($this->cureentConn);
                } catch (WebSocketException $e) {
                    $e->printStack();
                }
                // to avoid event leaks
                unset($this->clients[array_search($sock, $this->clients)], $readSocks[$kSock]);
                continue;
            }

            if (method_exists($this->handler, self::MAP_EVENT_TYPE_TO_METHODS[$dataType])) {
                try {
                    // dynamic call: onMessage, onPing, onPong
                    $this->handler->{self::MAP_EVENT_TYPE_TO_METHODS[$dataType]}($this->cureentConn, $dataPayload);
                } catch (WebSocketException $e) {
                    $e->printStack();
                }
            }
        }
    }

    /**
     * Message frames decoder
     *
     * @param string $data
     * @return mixed null on empty data|false on improper data|array - on success
     */
    private function decode(string $data)
    {
        if (empty($data)) {
            return NULL; // close has been sent
        }

        $unmaskedPayload = '';
        $decodedData = [];

        // estimate frame type:
        $firstByteBinary = sprintf('%08b', ord($data[0]));
        $secondByteBinary = sprintf('%08b', ord($data[1]));
        $opcode = bindec(substr($firstByteBinary, 4, 4));
        $isMasked = $secondByteBinary[0] === '1';
        $payloadLength = ord($data[1]) & self::MASK_127;

        // unmasked frame is received:
        if (!$isMasked) {
            return ['type' => '', 'payload' => '', 'error' => self::ERR_PROTOCOL];
        }

        switch ($opcode) {
            // text frame:
            case self::DECODE_TEXT:
                $decodedData['type'] = self::EVENT_TYPE_TEXT;
                break;
            case self::DECODE_BINARY:
                $decodedData['type'] = self::EVENT_TYPE_BINARY;
                break;
            // connection close frame:
            case self::DECODE_CLOSE:
                $decodedData['type'] = self::EVENT_TYPE_CLOSE;
                break;
            // ping frame:
            case self::DECODE_PING:
                $decodedData['type'] = self::EVENT_TYPE_PING;
                break;
            // pong frame:
            case self::DECODE_PONG:
                $decodedData['type'] = self::EVENT_TYPE_PONG;
                break;
            default:
                return ['type' => '', 'payload' => '', 'error' => self::ERR_UNKNOWN_OPCODE];
        }

        if ($payloadLength === self::MASK_126) {
            $mask = substr($data, 4, 4);
            $payloadOffset = self::PAYLOAD_OFFSET_8;
            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
        } elseif ($payloadLength === self::MASK_127) {
            $mask = substr($data, 10, 4);
            $payloadOffset = self::PAYLOAD_OFFSET_14;
            $tmp = '';
            for ($i = 0; $i < 8; $i++) {
                $tmp .= sprintf('%08b', ord($data[$i + 2]));
            }
            $dataLength = bindec($tmp) + $payloadOffset;
            unset($tmp);
        } else {
            $mask = substr($data, 2, 4);
            $payloadOffset = self::PAYLOAD_OFFSET_6;
            $dataLength = $payloadLength + $payloadOffset;
        }

        /**
         * We have to check for large frames here. socket_recv cuts at 1024 bytes
         * so if websocket-frame is > 1024 bytes we have to wait until whole
         * data is transferd.
         */
        if (strlen($data) < $dataLength) {
            return false;
        }

        if ($isMasked) {
            for ($i = $payloadOffset; $i < $dataLength; $i++) {
                $j = $i - $payloadOffset;
                if (isset($data[$i])) {
                    $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
                }
            }
            $decodedData['payload'] = $unmaskedPayload;
        } else {
            $payloadOffset -= 4;
            $decodedData['payload'] = substr($data, $payloadOffset);
        }

        return $decodedData;
    }

    /**
     * Handshakes/upgrade and key parse
     *
     * @param resource $client Source client socket to write
     * @param string $headers Headers that client has been sent
     * @return string   socket handshake key (Sec-WebSocket-Key)| false on parse error
     */
    private function handshake($client, string $headers) : string
    {
        $match = [];
        preg_match(self::SEC_WEBSOCKET_KEY_PTRN, $headers, $match);
        if (empty($match[1])) {
            return false;
        }

        $key = $match[1];
        $this->handshakes[(int)$client] = $key;
        // sending header according to WebSocket Protocol
        $secWebSocketAccept = base64_encode(sha1(trim($key) . self::HEADER_WEBSOCKET_ACCEPT_HASH, true));
        $this->setHeadersUpgrade($secWebSocketAccept);
        $upgradeHeaders = $this->getHeadersUpgrade();

        fwrite($client, $upgradeHeaders);
        return $key;
    }

    /**
     * Sets an array of headers needed to upgrade server/client connection
     *
     * @param string $secWebSocketAccept base64 encoded Sec-WebSocket-Accept header
     */
    private function setHeadersUpgrade($secWebSocketAccept) : void
    {
        $this->headersUpgrade = [
            self::HEADERS_UPGRADE_KEY              => self::HEADERS_UPGRADE_VALUE,
            self::HEADERS_CONNECTION_KEY           => self::HEADERS_CONNECTION_VALUE,
            self::HEADERS_SEC_WEBSOCKET_ACCEPT_KEY => ' ' . $secWebSocketAccept // the space before key is really important
        ];
    }

    /**
     * Retreives headers from an array of headers to upgrade server/client connection
     *
     * @return string   Headers to Upgrade communication connection
     */
    private function getHeadersUpgrade() : string
    {
        $handShakeHeaders = self::HEADER_HTTP1_1 . self::HEADERS_EOL;
        if (empty($this->headersUpgrade)) {
            die('Headers array is not set' . PHP_EOL);
        }
        foreach ($this->headersUpgrade as $key => $header) {
            $handShakeHeaders .= $key . ':' . $header . self::HEADERS_EOL;
            if ($key === self::HEADERS_SEC_WEBSOCKET_ACCEPT_KEY) { // add additional EOL fo Sec-WebSocket-Accept
                $handShakeHeaders .= self::HEADERS_EOL;
            }
        }
        return $handShakeHeaders;
    }

    /**
     * Parses parameters from GET on web-socket client connection before handshake
     *
     * @param string $headers
     */
    private function setPathParams(string $headers) : void
    {
        if (empty($this->handler->pathParams) === false) {
            $matches = [];
            preg_match('/GET\s(.*?)\s/', $headers, $matches);
            $left = $matches[1];
            foreach ($this->handler->pathParams as $k => $param) {
                if (empty($this->handler->pathParams[$k + 1]) && strpos($left, '/', 1) === false) {
                    // do not eat last char if there is no / at the end
                    $this->handler->pathParams[$param] = substr($left, strpos($left, '/') + 1);
                } else {
                    // eat both slashes
                    $this->handler->pathParams[$param] = substr($left, strpos($left, '/') + 1, strpos($left, '/', 1) - 1);
                }
                // clear the declaration of parsed param
                unset($this->handler->pathParams[array_search($param, $this->handler->pathParams, false)]);
                $left = substr($left, strpos($left, '/', 1));
            }
        }
    }
}
