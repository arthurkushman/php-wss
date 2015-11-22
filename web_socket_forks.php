<?php

/**
 * Create by Arthur Kushman
 */
class WebSocketServer {

  private $clients = [],
          $pathParams = [':entity', ':context', ':token'],
          $config = [],
          $handshakes = [],
          $userId;

  const MAX_BYTES_READ = 8192,
          HEADER_BYTES_READ = 1024;
  const TOKEN_LEN = 10;
  // must be the time for interaction between each client
  const STREAM_SELECT_TIMEOUT = 3600;
  // stream non-blocking 
  const NON_BLOCK = 0;

  public function __construct($config) {
    ini_set('default_socket_timeout', 5); // this should be >= 5 sec, otherwise there will be broken pipe - tested
    $this->config = $config;
  }

  public function run() {

    $server = stream_socket_server("tcp://{$this->config['host']}:{$this->config['port']}", $errno, $errorMessage);

    if ($server === false) {
      die("Could not bind to socket: $errno - $errorMessage");
    }

    while (true) {
      //prepare readable sockets
      $readSocks = $this->clients;
      $readSocks[] = $server;

      //start reading and use a large timeout
      if (!stream_select($readSocks, $write, $except, self::STREAM_SELECT_TIMEOUT)) {
        die('something went wrong while selecting');
      }

      print_r($readSocks);
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
          $totalClients = count($this->clients);

          // trigger OPEN event 
          $this->onOpen($newClient, ['total' => $totalClients,
              'socket_name' => $socketName], $this->pathParams);

          // handshake - then client source will be switched and counter incremented by 1 with reconect
          $this->handshake($newClient, $headers);
        }
        //delete the server socket from the read sockets
        unset($readSocks[array_search($server, $readSocks)]);
      }

      //message from existing client
      foreach ($readSocks as $sock) {

        $data = $this->decode(fread($sock, self::MAX_BYTES_READ));
//        print_r($data);
        if (!$data) {
          // trigger CLOSE event
          $this->onClose($sock);
          unset($this->clients[array_search($sock, $this->clients)]);
          @fclose($sock);
          echo "A client disconnected. Now there are total " . count($this->clients) . " clients.".PHP_EOL;
          continue;
        }

        // trigger MESSAGE event
        $this->onMessage($sock, $data);

//        if (!$this->handshake($sock)) {
//          unset($this->clients[intval($sock)]);
//          unset($this->handshakes[intval($sock)]);
//          $address = explode(':', stream_socket_get_name($sock, true));
//          if (isset($this->ips[$address[0]]) && $this->ips[$address[0]] > 0) {
//            @$this->ips[$address[0]] --;
//          }
//          @fclose($sock);
//        }

        /* if ($write) {
          echo 'write me....' . PHP_EOL;
          foreach ($write as $sock) {
          if (!$this->handshakes[intval($sock)]) {//если ещё не было получено рукопожатие от клиента
          continue; //то отвечать ему рукопожатием ещё рано
          }
          $info = $this->handshake($sock);
          //          echo $info;
          $this->onOpen($sock, $info); //вызываем пользовательский сценарий
          }
          } */


//        echo $this->handshake($sock);
//        fwrite($sock, 'Handshaked with client: ' . $client_socks[array_search($sock, $client_socks)]);
        //send the message back to client
//        fwrite($sock, $this->encode($data['payload'])); 
      }
    }

    /* while (true) {
      stream_select($read, $write, $except, null);

      if ($read) { // there is data from the client
      foreach ($read as $client) {
      print_r($client);die;
      $data = fread($client, self::MAX_BYTES_READ);

      if (!$data) { // connection was closed
      unset($this->clients[intval($client)]);
      @fclose($client);
      continue;
      }

      fwrite($worker, $data);
      }
      }
      } */

//    $m = new Memcached();
//    $m->addServer('localhost', 11211);
  }

  protected function encode($payload, $type = 'text', $masked = false) {
    $frameHead = array();
    $payloadLength = strlen($payload);

    switch ($type) {
      case 'text':
        // first byte indicates FIN, Text-Frame (10000001):
        $frameHead[0] = 129;
        break;

      case 'close':
        // first byte indicates FIN, Close Frame(10001000):
        $frameHead[0] = 136;
        break;

      case 'ping':
        // first byte indicates FIN, Ping frame (10001001):
        $frameHead[0] = 137;
        break;

      case 'pong':
        // first byte indicates FIN, Pong frame (10001010):
        $frameHead[0] = 138;
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
      case 1:
        $decodedData['type'] = 'text';
        break;

      case 2:
        $decodedData['type'] = 'binary';
        break;

      // connection close frame:
      case 8:
        $decodedData['type'] = 'close';

        break;

      // ping frame:
      case 9:
        $decodedData['type'] = 'ping';
        break;

      // pong frame:
      case 10:
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

//    if (!$key) {
//      echo 1;
//      echo PHP_EOL.$headers.PHP_EOL;
    preg_match('/Sec-WebSocket-Key:\s(.*)\n/', $headers, $match);
//      print_r($match);
    if (empty($match[1])) {
      return false;
    }

    $key = $match[1];

    $this->handshakes[intval($client)] = $key;
//      print_r($this->handshakes);
//    } else {
//      echo 2;
    //отправляем заголовок согласно протоколу вебсокета
//      $SecWebSocketAccept = base64_encode(pack('H*', sha1(trim($key) . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    $SecWebSocketAccept = base64_encode(sha1(trim($key) . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept:$SecWebSocketAccept\r\n\r\n";
    fwrite($client, $upgrade);
//      unset($this->handshakes[intval($client)]);
//    }

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
   * Called on connection from a new client
   * @param source $clientSock
   * @param array $info
   * @param array $pathParams
   */
  protected function onOpen($clientSock, $info, $pathParams) {
    echo 'Connection opend with client: ' . $clientSock . PHP_EOL;
    echo 'Info: ' . print_r($info, true) . PHP_EOL;
  }

  /**
   * 
   * @param source $clientSock  socket source to write to
   * @param array $data         data read from socket
   */
  private function onMessage($clientSock, $data) {
    $userId = array_search($clientSock, $this->clients);
//    $this->clients[$userId]; // get client sock 

    $json = json_decode($data['payload'], true);

    $answer = [];
    if ($json['ping']) {
      $answer['pong'] = 1;
    }

    fwrite($clientSock, $this->encode(json_encode($answer, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT)));
  }
  
  
  private function onClose($clientSock) {
    
  }

  private function onPing() {
    
  }

  private function onPong() {
    
  }

  private static function getUserId($token) {
    echo $token . PHP_EOL;
    return (int) mb_substr($token, self::TOKEN_LEN, null, 'utf-8');
  }

}

$websocketServer = new WebSocketServer([
    'host' => '0.0.0.0',
    'port' => 8000
        ]);
$websocketServer->run();
