<?php

namespace WSSC\Contracts;

/**
 *
 * @author Arthur Kushman
 */
interface WebSocketServerContract
{
    // HOST/PORT
    public const DEFAULT_HOST = '0.0.0.0';
    public const DEFAULT_PORT = 8000;

    // ENCODER/DECODER ERRORS
    public const ERR_PROTOCOL        = 'protocol error (1002)';
    public const ERR_UNKNOWN_OPCODE  = 'unknown opcode (1003)';
    public const ERR_FRAME_TOO_LARGE = 'frame too large (1004)';

    // Headers 
    public const HEADER_HTTP1_1                   = 'HTTP/1.1 101 Web Socket Protocol Handshake';
    public const HEADER_WEBSOCKET_ACCEPT_HASH     = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    public const HEADERS_UPGRADE_KEY              = 'Upgrade',
                 HEADERS_CONNECTION_KEY           = 'Connection',
                 HEADERS_SEC_WEBSOCKET_ACCEPT_KEY = 'Sec-WebSocket-Accept';
    public const HEADERS_UPGRADE_VALUE            = 'websocket',
                 HEADERS_CONNECTION_VALUE         = 'Upgrade';
    public const HEADERS_EOL                      = "\r\n";
    public const SEC_WEBSOCKET_KEY_PTRN           = '/Sec-WebSocket-Key:\s(.*)\n/';

    // PAYLOAD OFFSETS
    public const PAYLOAD_OFFSET_6 = 6,
        PAYLOAD_OFFSET_8 = 8,
        PAYLOAD_OFFSET_14 = 14;

    // limits
    public const CLIENTS_PER_FORK      = 1000;
    public const STREAM_SELECT_TIMEOUT = 3600;

    public const PROC_TITLE = 'php-wss';

    public function run();

}
