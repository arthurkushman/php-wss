# php-wss
Web-socket server application with multi-process and parse templates support 

## Library comes with 3 main options

- it`s a web-socket server for multiple connections with decoding/encoding for all events out of the box
- it has GET uri parser, so You can easilly use any templates 
- multiple process per user connections support, so You can fork processes to speed up performance deciding how many client-connections should be there

## How do I get set up?

Preferred way to install is with Composer.

Just add

"require": {
  "arthurkushman/php-wss": "1.0.*"
}

in your projects composer.json.

OR

Get the class web_socket_server.php and run it as CLI service

PS U`ll see the processes increase named "php-wss" as connections will grow and decrease while stack will lessen.
