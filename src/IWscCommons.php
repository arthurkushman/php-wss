<?php

namespace WSSC;

/**
 *
 * @author Arthur Kushman
 */
interface IWscCommons {

    const MAX_BYTES_READ = 65535,
            DEFAULT_TIMEOUT = 5,
            DEFAULT_FRAGMENT_SIZE = 4096,
            DEFAULT_RESPONSE_HEADER = 1024;
    const SEC_WEBSOCKET_ACCEPT_PTTRN = '/Sec-WebSocket-Accept:\s(.*)$/mUi';
    const SERVER_KEY_ACCEPT = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    // MASKS
    const MASK_125 = 125,
            MASK_126 = 126,
            MASK_127 = 127,
            MASK_128 = 128,
            MASK_254 = 254,
            MASK_255 = 255;

    const KEY_GEN_LENGTH = 16;
    
    function connect();
    
    function read();
    
    function write();
    
    function send();
    
    function close();
    
    function receive();
}
