<?php
namespace WSSC;
/**
 *
 * @author Arthur Kushman
 */
interface IWebSocket {
    
    function onOpen(IConnection $conn);
    
    function onClose(IConnection $conn);
    
    function onError(IConnection $conn, WebSocketException $ex);
}
