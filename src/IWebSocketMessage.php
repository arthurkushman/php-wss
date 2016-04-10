<?php
namespace WSSC;
/**
 *
 * @author Arthur Kushman
 */
interface IWebSocketMessage extends IWebSocket, IMessage {
    
    /**
     * You may want to implement these methods to bring ping/pong events
     * @param \WSSC\IConnection $conn
     * @param type $msg
     */
//    function onPing(IConnection $conn, $msg);
//    function onPong(IConnection $conn, $msg);
}
