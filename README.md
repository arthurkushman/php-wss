# php-wss
Web-socket server/client with multi-process and parse templates support on server and send/receive options on client

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/arthurkushman/php-wss/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/arthurkushman/php-wss/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/arthurkushman/php-wss/badges/build.png?b=master)](https://scrutinizer-ci.com/g/arthurkushman/php-wss/build-status/master)
[![Latest Stable Version](https://poser.pugx.org/arthurkushman/php-wss/v/stable)](https://packagist.org/packages/arthurkushman/php-wss)
[![Total Downloads](https://poser.pugx.org/arthurkushman/php-wss/downloads)](https://packagist.org/packages/arthurkushman/php-wss)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

## Library comes with several main options
Server:
- it`s a web-socket server for multiple connections with decoding/encoding for all events out of the box (with Dependency Injected MessageHandler)
- it has GET uri parser, so you can easily use any templates
- multiple process per user connections support, so you can fork processes to speed up performance deciding how many client-connections should be there
- broadcasting message(s) to all clients
- origin check
- ssl server run

Client:
- You have the ability to handshake (which is performed automatically) and send messages to server
- Receive a response from the server
- Initiate connection via proxy

## How do I get set up?

Preferred way to install is with Composer.

perform command in shell

```bash
 composer require arthurkushman/php-wss
```

OR

just add

```javascript
"require": {
  "arthurkushman/php-wss": ">=1.3"
}
```

to your projects composer.json.

### Implement your WebSocket handler class - ex.:

```php
<?php
use WSSC\Contracts\ConnectionContract;
use WSSC\Contracts\WebSocket;
use WSSC\Exceptions\WebSocketException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

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

    private $log;

    /**
     * ServerHandler constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        // create a log channel
        $this->log = new Logger('ServerSocket');
        $this->log->pushHandler(new StreamHandler('./tests/tests.log'));
    }

    public function onOpen(ConnectionContract $conn)
    {
        $this->clients[$conn->getUniqueSocketId()] = $conn;
        $this->log->debug('Connection opend, total clients: ' . count($this->clients));
    }

    public function onMessage(ConnectionContract $recv, $msg)
    {
        $this->log->debug('Received message:  ' . $msg);
        $recv->send($msg);
    }

    public function onClose(ConnectionContract $conn)
    {
        unset($this->clients[$conn->getUniqueSocketId()]);
        $this->log->debug('close: ' . print_r($this->clients, 1));
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
     *
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
To save clients with their unique ids - use `getUniqueSocketId()` which returns (type-casted to int) socketConnection resource id.

### Then put code bellow to Your CLI/Console script and run

```php
<?php
use WSSC\WebSocketServer;
use WSSCTEST\ServerHandler;
use WSSC\Components\ServerConfig;

$config = new ServerConfig();
$config->setClientsPerFork(2500);
$config->setStreamSelectTimeout(2 * 3600);

$webSocketServer = new WebSocketServer(new ServerHandler(), $config);
$webSocketServer->run();
```

### How do I set WebSocket Client connection?

```php
<?php
use WSSC\WebSocketClient;
use \WSSC\Components\ClientConfig;

$client = new WebSocketClient('ws://localhost:8000/notifications/messanger/yourtoken123', new ClientConfig());
$client->send('{"user_id" : 123}');
echo $client->receive();
```

That`s it, client is just sending any text content (message) to the Server.

Server reads all the messages and push them to Handler class, for further custom processing.

### How to pass an optional timeout, headers, fragment size etc?
You can pass optional configuration to `WebSocketClient`'s constructor e.g.:
```php
<?php
use WSSC\WebSocketClient;
use WSSC\Components\ClientConfig;

$config = new ClientConfig();
$config->setFragmentSize(8096);
$config->setTimeout(15);
$config->setHeaders([
    'X-Custom-Header' => 'Foo Bar Baz',
]);

// if proxy settings is of need
$config->setProxy('127.0.0.1', '80');
$config->setProxyAuth('proxyUser', 'proxyPass');

$client = new WebSocketClient('ws://localhost:8000/notifications/messanger/yourtoken123', $config);
```
If it is of need to send ssl requests just set `wss` scheme to constructors url param of `WebSocketClient` - it will be passed and used as ssl automatically.

You can also set particular context options for `stream_context_create` to provide them to `stream_socket_client`, for instance:
```php
$config = new ClientConfig();
$config->setContextOptions(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
```
or any other available options see - https://www.php.net/manual/en/context.php.

### BroadCasting
You may wish to broadcast messages by simply calling `broadCast` method on `Connection` object in any method of your `ServerHandler` class:
```php
$conn->broadCast('hey everybody...');

// or to send multiple messages with 2 sec delay between them
$conn->broadCastMany(['Hello', 'how are you today?', 'have a nice day'], 2);
```

### Origin check
To let server check the Origin header with `n` hosts provided:
```php
$config = new ServerConfig();
$config->setOrigins(["example.com", "otherexample.com"]);
$websocketServer = new WebSocketServer(new ServerHandler(), $config);
$websocketServer->run();
```
Server will automatically check those hosts proceeding to listen for other connections even if some failed to pass check.

### SSL Server run options
```php
use WSSC\Components\ServerConfig;
use WSSC\WebSocketServer;

$config = new ServerConfig();
$config->setIsSsl(true)->setAllowSelfSigned(true)
    ->setCryptoType(STREAM_CRYPTO_METHOD_SSLv23_SERVER)
    ->setLocalCert("./tests/certs/cert.pem")->setLocalPk("./tests/certs/key.pem")
    ->setPort(8888);

$websocketServer = new WebSocketServer(new ServerHandler(), $config);
$websocketServer->run();
```

### How to test

To run the Server - execute from the root of a project:
```php
phpunit --bootstrap ./tests/_bootstrap.php ./tests/WebSocketServerTest.php
```

To run the Client - execute in another console:
```php
phpunit --bootstrap ./tests/_bootstrap.php ./tests/WebSocketClientTest.php
```

PHP7 support since version 1.3 - with types, returns and better function implementations.

Benchmarks:


| iter | benchmark           | subject      | set | revs  | mem_peak   | time_avg | comp_z_value | comp_deviation |
| ---  | ---                 | ---          | --- |  ---  |   ---      |   ---    |    ---       |      ---       |
| 0    | MaxConnectionsBench | benchConnect |     | 10000 | 1,547,496b | 4.831μs  | +1.41σ       | +6.35%         |
| 1    | MaxConnectionsBench | benchConnect |     | 10000 | 1,547,496b | 4.372μs  | -0.83σ       | -3.76%         |
| 2    | MaxConnectionsBench | benchConnect |     | 10000 | 1,547,496b | 4.425μs  | -0.57σ       | -2.59%         |


| benchmark           | subject      | revs  | its | mem_peak | mode    | rstdev |
|   ---               | ---          | ---   | --- |  ---     | ---     | ---    |
| MaxConnectionsBench | benchConnect | 10000 | 3   | 1.547mb  | 4.427μs | ±4.51% |

As you may have been noticed, average time to send msg is `4.427μs` which is roughly rounded to `4` microseconds 
within 10 000 have been sent 3 times in a row.

PS U'll see the processes increase named "php-wss" as CPP (Connections Per-Process) will grow and decrease while stack will lessen.
For instance, if set 100 CPP and there are 128 connections - You will be able to see 2 "php-wss" processes with for ex.: `ps aux | grep php-wss`

Used by:

![alt Avito logo](https://github.com/SoliDry/laravel-api/blob/master/tests/images/avito_logo.png)

Supporters gratitude:

<img src="https://github.com/SoliDry/laravel-api/blob/master/tests/images/jetbrains-logo.png" alt="JetBrains logo" width="200" height="166" />