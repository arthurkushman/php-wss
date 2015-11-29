# php-wss
Web-socket server application with multi-process and parse templates support 

## Library comes with 3 main options

- it`s a web-socket server for multiple connections with decoding/encoding for all events out of the box
- it has GET url parser, so You can easilly use any templates U need
- multiple process per user connections support, so You can fork processes to speed up performance

## How do I get set up?

Just get the class web_socket_server.php and run it as cli service

PS U`ll see the processes increase named "php-wss" as connections will grow and decrease while stack will lessen
