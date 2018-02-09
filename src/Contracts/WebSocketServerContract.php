<?php

namespace WSSC\Contracts;

/**
 *
 * @author Arthur Kushman
 */
interface WebSocketServerContract
{

    // HOST/PORT
    const DEFAULT_HOST = '0.0.0.0';
    const DEFAULT_PORT = 8000;
    // ENCODER/DECODER ERRORS
    const ERR_PROTOCOL        = 'protocol error (1002)';
    const ERR_UNKNOWN_OPCODE  = 'unknown opcode (1003)';
    const ERR_FRAME_TOO_LARGE = 'frame too large (1004)';

    // Headers 
    const HEADER_HTTP1_1                   = 'HTTP/1.1 101 Web Socket Protocol Handshake';
    const HEADER_WEBSOCKET_ACCEPT_HASH     = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    const HEADERS_UPGRADE_KEY              = 'Upgrade',
          HEADERS_CONNECTION_KEY           = 'Connection',
          HEADERS_SEC_WEBSOCKET_ACCEPT_KEY = 'Sec-WebSocket-Accept';
    const HEADERS_UPGRADE_VALUE            = 'websocket',
          HEADERS_CONNECTION_VALUE         = 'Upgrade';
    const HEADERS_EOL                      = "\r\n";
    const SEC_WEBSOCKET_KEY_PTRN           = '/Sec-WebSocket-Key:\s(.*)\n/';

    // PAYLOAD OFFSETS
    const PAYLOAD_OFFSET_6 = 6,
        PAYLOAD_OFFSET_8 = 8,
        PAYLOAD_OFFSET_14 = 14;

    public function run();

}
