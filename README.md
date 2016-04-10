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

perform comman in shell

```bash
$ composer require arthurkushman/php-wss
```

### Then put code bellow to Your CLI script and run

```php
$websocketServer = new WebSocketServer(new ServerMessageHandler(), [
    'host' => '0.0.0.0',
    'port' => 8000
        ]);
$websocketServer->run();
```

OR 

Get the class web_socket_server.php and run it as CLI service

PS U`ll see the processes increase named "php-wss" as connections will grow and decrease while stack will lessen.
