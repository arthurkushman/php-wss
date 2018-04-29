# php-wss
Web-socket server/client with multi-process and parse templates support on server and send/receive options on client

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/arthurkushman/php-wss/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/arthurkushman/php-wss/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/arthurkushman/php-wss/badges/build.png?b=master)](https://scrutinizer-ci.com/g/arthurkushman/php-wss/build-status/master)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

## Library comes with 5 main options
Server:
- it`s a web-socket server for multiple connections with decoding/encoding for all events out of the box (with Dependency Injected MessageHandler)
- it has GET uri parser, so You can easily use any templates 
- multiple process per user connections support, so You can fork processes to speed up performance deciding how many client-connections should be there

Client: 
- You have the ability to handshake (which is performed automatically) and send messages to server
- Receive a response from the server

## How do I get set up?

Preferred way to install is with Composer.

Just add 

```javascript
"require": {
  "arthurkushman/php-wss": ">=1.3"  
}
```

in your projects composer.json.

OR 

perform command in shell

```bash
 composer require arthurkushman/php-wss
```

### Implement Your WebSocket handler class - ex.:

```php
<?php
use WSSC\Contracts\ConnectionContract;
use WSSC\Contracts\WebSocket;
use WSSC\Exceptions\WebSocketException;

class ServerHandler extends WebSocket
{

    /*
     *  if You need to parse URI context like /messanger/chat/JKN324jn4213
     *  You can do so by placing URI parts into an array - $pathParams, when Socket will receive a connection 
     *  this variable will be appropriately set to key => value pairs, ex.: ':context' => 'chat'
     *  Otherwise leave $pathParams as an empty array
     */

    public $pathParams = [':entity', ':context', ':token'];
    private $clients = [];

    public function onOpen(ConnectionContract $conn)
    {
        $this->clients[] = $conn;
        echo 'Connection opend, total clients: ' . count($this->clients) . PHP_EOL;
    }

    public function onMessage(ConnectionContract $recv, $msg)
    {
        echo 'Received message:  ' . $msg . PHP_EOL;
        $recv->send($msg);
    }

    public function onClose(ConnectionContract $conn)
    {
        $conn->send('whatever you need');
        unset($this->clients[array_search($conn, $this->clients)]);
        $conn->close();
    }

    /**
     * @param ConnectionContract $conn
     * @param WebSocketException $ex
     */
    public function onError(ConnectionContract $conn, WebSocketException $ex)
    {
        echo 'Error occured: ' . $ex->printStack();
    }

    /**
     * You may want to implement these methods to bring ping/pong events
     * @param ConnectionContract $conn
     * @param string $msg
     */
    public function onPing(ConnectionContract $conn, $msg)
    {
        // TODO: Implement onPing() method.
    }

    /**
     * @param ConnectionContract $conn
     * @param $msg
     * @return mixed
     */
    public function onPong(ConnectionContract $conn, $msg)
    {
        // TODO: Implement onPong() method.
    }
}

```

### Then put code bellow to Your CLI/Console script and run 

```php
$websocketServer = new WebSocketServer(new ServerHandler(), [
    'host' => '0.0.0.0',
    'port' => 8000
        ]);
$websocketServer->run(); 
```

## How do I set WebSocket Client connection?

```php
use WSSC\WebSocketClient;

$client = new WebSocketClient('ws://localhost:8000/notifications/messanger/yourtoken123');
$client->send('{"user_id" : 123}');
echo $client->receive();
```

That`s it, client is just sending any text content (message) to the Server.

Server reads all the messages and push them to Handler class, for further custom processing.

## How to test

To run the Server - execute from the root of a project:
```php
phpunit --bootstrap ./tests/_bootstrap.php ./tests/WebSocketServerTest.php
```

To run the Client - execute in another console:
```php
phpunit --bootstrap ./tests/_bootstrap.php ./tests/WebSocketClientTest.php
```

PHP7 support since version 1.3 - with types, returns and better function implementations. 

PS U'll see the processes increase named "php-wssc" as CPP (Connections Per-Process) will grow and decrease while stack will lessen. 
For instance, if set 100 CPP and there are 128 connections - You will be able to see 2 "php-wssc" processes with for ex.: `ps aux | grep php-wssc`
