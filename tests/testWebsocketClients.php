<?php

/**
 * Create by Arthur Kushman
 */
class TestWebSocketClients extends PHPUnit_Framework_TestCase {

  public function setUp() {
    require_once __DIR__ . '/../ws-client.php';
  }

  public function testHundredClients() {

    for ($i = 0; $i < 5;  ++$i) {
      $WebSocketClient[$i] = new WebsocketClient('127.0.0.1', 8000);
      echo $WebSocketClient[$i]->sendData('Client connected ' . $i);
    }
//    sleep(10);
//    unset($WebSocketClient);
  }

}
