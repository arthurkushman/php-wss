<?php
namespace WSSC;
/**
 *
 * @author Arthur Kushman
 */
interface IMessage {
    
    function onMessage(IConnection $recv, $msg);
    
}
