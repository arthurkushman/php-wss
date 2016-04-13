<?php

namespace WSSC;
require_once './src/ConnectionException.php';
require_once './src/IWscCommons.php';
require_once './src/WscMain.php';
require_once './src/WebSocketClient.php';

$client = new WebSocketClient('ws://localhost:8000/notifications/messanger/vkjsndfvjn23243');
$client->send('{"user_id" : 123}');
echo $client->receive();
