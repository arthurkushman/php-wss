<?php
namespace WSSC;
/**
 *
 * @author Arthur Kushman
 */
interface IConnection {
    
    function send($data);
    
    function close();
    
}
