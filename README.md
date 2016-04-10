# php-wss
Web-socket server application with multi-process and parse templates support 

## Library comes with 3 main options

- it`s a web-socket server for multiple connections with decoding/encoding for all events out of the box
- it has GET uri parser, so You can easilly use any templates 
- multiple process per user connections support, so You can fork processes to speed up performance deciding how many client-connections should be there

## How do I get set up?

Preferred way to install is with Composer.

Just add

```javascript
"require": {

  "arthurkushman/php-wss": "1.0.*"
  
}
```

in your projects composer.json.

OR 

perform command in shell

```bash
$ composer require arthurkushman/php-wss
```

### Implement Your WebSocket handler class - ex.:

```php
use WSSC\IWebSocketMessage;
use WSSC\IConnection;
use WSSC\WebSocketException;

class ServerMessageHandler implements IWebSocketMessage {
    /*
     *  if You need to parse URI context like /messanger/chat/JKN324jn4213
     *  You can do so by placing URI parts into an array - $pathParams, when Socket will receive a connection 
     *  this variable will be appropriately set to key => value pairs, ex.: ':context' => 'chat'
     *  Otherwise leave $pathParams as an empty array
     */

    public $pathParams = [':entity', ':context', ':token'];
    private $clients = [];

    public function onOpen(IConnection $conn) {
        $this->clients[] = $conn;
        echo 'Connection opend, total clients: ' . count($this->clients) . PHP_EOL;
    }

    public function onMessage(IConnection $recv, $msg) {        
        $recv->send($msg);
    }

    public function onClose(IConnection $conn) {
        unset($this->clients[array_search($conn, $this->clients)]);
        $conn->close();
    }

    public function onError(IConnection $conn, WebSocketException $ex) {
        echo 'Error occured: '.$ex->printStack();
    }

}
```

### Then put code bellow to Your CLI/Console script and run 

```php
$websocketServer = new WebSocketServer(new ServerMessageHandler(), [
    'host' => '0.0.0.0',
    'port' => 8000
        ]);
$websocketServer->run(); 
```

PS U'll see the processes increase named "php-wss" as CPP (Connections Per-Process) connections will grow and decrease while stack will lessen. 
For instance, I set 100 CPP, if there are 128 connections - You will be able to see 2 "php-wss" process with for ex.: ps aux | grep php-wss
