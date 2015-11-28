<?php

/**
 * Create by Arthur Kushman
 */
class WebSocketServer {

  private $clients = [],
          // set any template You need ex.: GET /subscription/messenger/token
          $pathParams = [':entity',  ':context', ':token'],
          $config = [],
          $handshakes = [],
          $userId,
          $totalClients = 0, 
          $maxClients = 1;

  // for the very 1st time must be true
  protected $stepRecursion = true;
  
  const MAX_BYTES_READ = 8192,
          HEADER_BYTES_READ = 1024;
  const TOKEN_LEN = 10;
  // must be the time for interaction between each client
  const STREAM_SELECT_TIMEOUT = 3600;
  // stream non-blocking 
  const NON_BLOCK = 0;
  // max clients to fork another process
  const MAX_CLIENTS_REMAINDER_FORK = 2;
  
  /**
   * CAUTION - PARAMETERS BELOW CAN`T BE CHANGED
   */
  const PAYLOAD_MAX_BITS = 65535;
  // title to use in ps, htop etc
  const PROC_TITLE = 'php-wss';
  
  const DECODE_TEXT = 1, 
        DECODE_BINARY = 2, 
          DECODE_CLOSE = 8, 
          DECODE_PING = 9, 
          DECODE_PONG = 10;
  
  const ENCODE_TEXT = 129, 
          ENCODE_CLOSE = 136,
          ENCODE_PING = 137, 
          ENCODE_PONG = 138;  
  // =============================================
  
  public function __construct($config) {
    ini_set('default_socket_timeout', 5); // this should be >= 5 sec, otherwise there will be broken pipe - tested
    $this->config = $config;
  }

  /**
   * Runs main process - Anscestor with server socket on TCP 
   */
  public function run() {
    $server = stream_socket_server("tcp://{$this->config['host']}:{$this->config['port']}", $errno, $errorMessage);

    if ($server === false) {
      die("Could not bind to socket: $errno - $errorMessage");
    }
    cli_set_process_title(self::PROC_TITLE);
    $this->eventLoop($server);
  }

  /**
   * Recursive event loop that input intu recusion by remainder = 0 - thus when N users, 
   * and when forks equals true which prevents it from infinite recursive iterations
   * @param source $server  server connection
   * @param bool $fork      flag to fork or run event loop
   */
  private function eventLoop($server, $fork = false) {
    if ($fork === true) {
      $pid = pcntl_fork();
      
      if ($pid) { // run eventLoop in parent        
        cli_set_process_title(self::PROC_TITLE);
        $this->eventLoop($server);
      }      
    } else {
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
        
        if ($this->totalClients !== 0 && $this->totalClients % self::MAX_CLIENTS_REMAINDER_FORK === 0 
                && $this->maxClients > $this->totalClients) { // there is less connection for amount of processes at this moment
          exit(1);
        }        
        
        //prepare readable sockets
        $readSocks = $this->clients;
        $readSocks[] = $server;

        //start reading and use a large timeout
        if (!stream_select($readSocks, $write, $except, self::STREAM_SELECT_TIMEOUT)) {
          die('something went wrong while selecting');
        }

//        print_r($readSocks);
//      print_r($write);
        //new client
        if (in_array($server, $readSocks)) {
          $newClient = stream_socket_accept($server, 0); // must be 0 to non-block          
          if ($newClient) {
            // print remote client information, ip and port number
            $socketName = stream_socket_get_name($newClient, true);

            // important to read from headers here coz later client will change and there will be only msgs on pipe
            $headers = fread($newClient, self::HEADER_BYTES_READ);
            $this->getPathParams($headers);

            $this->userId = self::getUserId($this->pathParams[':token']);
            $this->clients[$this->userId] = $newClient; // add client with his id from token and save his $sock source and then search them by $sock                   
            $this->stepRecursion = true; // set on new client coz of remainder % is always 0
            // trigger OPEN event 
            $this->onOpen($newClient, ['total' => $this->totalClients,
                'socket_name' => $socketName]);

            // handshake - then client source will be switched and counter incremented by 1 with reconect
            $this->handshake($newClient, $headers);
          }
          //delete the server socket from the read sockets
          unset($readSocks[array_search($server, $readSocks)]);
        }

        //message from existing client
        foreach ($readSocks as $kSock => $sock) {

          $data = $this->decode(fread($sock, self::MAX_BYTES_READ));
//        print_r($data);
          if (empty($data) || $data['type'] === 'close') { // close event triggered from client - browser tab or close socket event
            // trigger CLOSE event
            $this->onClose($sock);
            unset($this->clients[array_search($sock, $this->clients)]);
            @fclose($sock);
            echo 'A client disconnected. Now there are total: ' . count($this->clients) . ' clients.' . PHP_EOL;
            unset($readSocks[$kSock]); // to avoid event leaks
            continue;
          }
          
          if ($data['type'] === 'text') {
            // trigger MESSAGE event
            $this->onMessage($sock, $data);            
          }
          
          if ($data['type'] === 'ping') {
            // trigger PING event
            $this->onPing($sock, $data);
          }

          if ($data['type'] === 'pong') {
            // trigger PONG event
            $this->onPong($sock, $data);
          }          
          
        }
      }
    }
  }

  protected function encode($payload, $type = 'text', $masked = false) {
    $frameHead = array();
    $payloadLength = strlen($payload);

    switch ($type) {
      case 'text':
        // first byte indicates FIN, Text-Frame (10000001):
        $frameHead[0] = self::ENCODE_TEXT;
        break;

      case 'close':
        // first byte indicates FIN, Close Frame(10001000):
        $frameHead[0] = self::ENCODE_CLOSE;
        break;

      case 'ping':
        // first byte indicates FIN, Ping frame (10001001):
        $frameHead[0] = self::ENCODE_PING;
        break;

      case 'pong':
        // first byte indicates FIN, Pong frame (10001010):
        $frameHead[0] = self::ENCODE_PONG;
        break;
    }

    // set mask and payload length (using 1, 3 or 9 bytes)
    if ($payloadLength > 65535) {
      $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
      $frameHead[1] = ($masked === true) ? 255 : 127;
      for ($i = 0; $i < 8; $i++) {
        $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
      }
      // most significant bit MUST be 0
      if ($frameHead[2] > 127) {
        return array('type' => '', 'payload' => '', 'error' => 'frame too large (1004)');
      }
    } elseif ($payloadLength > 125) {
      $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
      $frameHead[1] = ($masked === true) ? 254 : 126;
      $frameHead[2] = bindec($payloadLengthBin[0]);
      $frameHead[3] = bindec($payloadLengthBin[1]);
    } else {
      $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
    }

    // convert frame-head to string:
    foreach (array_keys($frameHead) as $i) {
      $frameHead[$i] = chr($frameHead[$i]);
    }
    if ($masked === true) {
      // generate a random mask:
      $mask = array();
      for ($i = 0; $i < 4; $i++) {
        $mask[$i] = chr(rand(0, 255));
      }

      $frameHead = array_merge($frameHead, $mask);
    }
    $frame = implode('', $frameHead);

    // append payload to frame:
    for ($i = 0; $i < $payloadLength; $i++) {
      $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
    }

    return $frame;
  }

  /**
   * Message frames decoder
   * @param type $data
   * @return boolean
   */
  protected function decode($data) {
    if (empty($data)) return null; // close has been sent
      
    $unmaskedPayload = '';
    $decodedData = array();

    // estimate frame type:
    $firstByteBinary = sprintf('%08b', ord($data[0]));
    $secondByteBinary = sprintf('%08b', ord($data[1]));
    $opcode = bindec(substr($firstByteBinary, 4, 4));
    $isMasked = ($secondByteBinary[0] == '1') ? true : false;
    $payloadLength = ord($data[1]) & 127;

    // unmasked frame is received:
    if (!$isMasked) {
      return array('type' => '', 'payload' => '', 'error' => 'protocol error (1002)');
    }

    switch ($opcode) {
      // text frame:
      case self::DECODE_TEXT:
        $decodedData['type'] = 'text';
        break;
      case self::DECODE_BINARY:
        $decodedData['type'] = 'binary';
        break;
      // connection close frame:
      case self::DECODE_CLOSE:
        $decodedData['type'] = 'close';        
        break;
      // ping frame:
      case self::DECODE_PING:
        $decodedData['type'] = 'ping';
        break;
      // pong frame:
      case self::DECODE_PONG:
        $decodedData['type'] = 'pong';
        break;
      default:
        return array('type' => '', 'payload' => '', 'error' => 'unknown opcode (1003)');
    }

    if ($payloadLength === 126) {
      $mask = substr($data, 4, 4);
      $payloadOffset = 8;
      $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
    } elseif ($payloadLength === 127) {
      $mask = substr($data, 10, 4);
      $payloadOffset = 14;
      $tmp = '';
      for ($i = 0; $i < 8; $i++) {
        $tmp .= sprintf('%08b', ord($data[$i + 2]));
      }
      $dataLength = bindec($tmp) + $payloadOffset;
      unset($tmp);
    } else {
      $mask = substr($data, 2, 4);
      $payloadOffset = 6;
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
      $payloadOffset = $payloadOffset - 4;
      $decodedData['payload'] = substr($data, $payloadOffset);
    }

    return $decodedData;
  }

  /**
   * Handshakes/upgrade and key parse
   * @param source $client  Source client socket to write
   * @return string         socket handshake key (Sec-WebSocket-Key)
   */
  protected function handshake($client, $headers) {
    $key = empty($this->handshakes[intval($client)]) ? 0 : $this->handshakes[intval($client)];
    
    preg_match('/Sec-WebSocket-Key:\s(.*)\n/', $headers, $match);

    if (empty($match[1])) {
      return false;
    }

    $key = $match[1];

    $this->handshakes[intval($client)] = $key;

    //отправляем заголовок согласно протоколу вебсокета
    $SecWebSocketAccept = base64_encode(sha1(trim($key) . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept:$SecWebSocketAccept\r\n\r\n";
    fwrite($client, $upgrade);

    return $key;
  }

  /**
   * Parses parameters from GET on web-socket client connection before handshake
   * @param string $headers
   */
  private function getPathParams($headers) {
    $matches = [];
    preg_match('/GET\s(.*?)\s/', $headers, $matches);
    $left = $matches[1];
    foreach ($this->pathParams as $k => $param) {
      if (empty($this->pathParams[$k + 1]) && strpos($left, '/', 1) === false) {
        // do not eat last char if there is no / at the end
        $this->pathParams[$param] = substr($left, strpos($left, '/') + 1);
      } else {
        // eat both slashes
        $this->pathParams[$param] = substr($left, strpos($left, '/') + 1, strpos($left, '/', 1) - 1);
      }
      $left = substr($left, strpos($left, '/', 1));
    }
  }

  /**
   * Triggers after GET parse, handshake 
   * @param source $clientSock
   * @param array $info
   */
  protected function onOpen($clientSock, $info) {    
    echo 'Connection opend with client: ' . $clientSock . PHP_EOL;
    echo 'Info: ' . print_r($info, true) . PHP_EOL;
  }

  /**
   * Triggers after GET parse, handshake and onOpen
   * @param source $clientSock  socket source to write to
   * @param array $data         data read from socket
   */
  private function onMessage($clientSock, $data) {    
    $json = json_decode($data['payload'], true);

    $answer = [];
    if ($json['ping']) {
      $answer['pong'] = 1;
    }
    $answer['my_client_id'] = $this->userId;
    fwrite($clientSock, $this->encode(json_encode($answer, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT)));
  }

  /**
   * Triggers when client disconnects or on net technical disruption
   * here You can clear some sources, close db connection etc
   * @param source $clientSock
   */
  private function onClose($clientSock) {
    echo 'Connection has been closed from a client'
    .$this->clients[array_search($clientSock, $this->clients)].
            ', server closing connection...'.PHP_EOL;    
  }

  private function onPing($clientSock, $data) {
    
  }

  private function onPong($clientSock, $data) {
    
  }

  private static function getUserId($token) {
    return (int) mb_substr($token, self::TOKEN_LEN, null, 'utf-8');
  }

}

$websocketServer = new WebSocketServer([
    'host' => '0.0.0.0',
    'port' => 8000
        ]);
$websocketServer->run();
