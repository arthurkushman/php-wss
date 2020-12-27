<?php

namespace WSSC\Contracts;

/**
 *
 * @author Arthur Kushman
 */
interface CommonsContract
{
    // DADA types
    public const EVENT_TYPE_PING         = 'ping';
    public const EVENT_TYPE_PONG         = 'pong';
    public const EVENT_TYPE_TEXT         = 'text';
    public const EVENT_TYPE_CLOSE        = 'close';
    public const EVENT_TYPE_BINARY       = 'binary';
    public const EVENT_TYPE_CONTINUATION = 'continuation';

    public const MAP_EVENT_TYPE_TO_METHODS = [
        self::EVENT_TYPE_TEXT => 'onMessage',
        self::EVENT_TYPE_PING => 'onPing',
        self::EVENT_TYPE_PONG => 'onPong',
    ];

    // DECODE FRAMES
    public const DECODE_TEXT   = 1;
    public const DECODE_BINARY = 2;
    public const DECODE_CLOSE  = 8;
    public const DECODE_PING   = 9;
    public const DECODE_PONG   = 10;

    // ENCODE FRAMES
    public const ENCODE_TEXT  = 129;
    public const ENCODE_CLOSE = 136;
    public const ENCODE_PING  = 137;
    public const ENCODE_PONG  = 138;

    // MASKS
    public const MASK_125 = 125;
    public const MASK_126 = 126;
    public const MASK_127 = 127;
    public const MASK_128 = 128;
    public const MASK_254 = 254;
    public const MASK_255 = 255;

    // PAYLOADS
    public const PAYLOAD_CHUNK    = 8;
    public const PAYLOAD_MAX_BITS = 65535;

    // transfer protocol-level errors
    public const SERVER_COULD_NOT_BIND_TO_SOCKET = 101;
    public const SERVER_SELECT_ERROR             = 102;
    public const SERVER_HEADERS_NOT_SET          = 103;
    public const CLIENT_COULD_NOT_OPEN_SOCKET    = 104;
    public const CLIENT_INCORRECT_SCHEME         = 105;
    public const CLIENT_INVALID_UPGRADE_RESPONSE = 106;
    public const CLIENT_INVALID_STREAM_CONTEXT   = 107;
    public const CLIENT_BAD_OPCODE               = 108;
    public const CLIENT_COULD_ONLY_WRITE_LESS    = 109;
    public const CLIENT_BROKEN_FRAME             = 110;
    public const CLIENT_EMPTY_READ               = 111;
    public const SERVER_INVALID_STREAM_CONTEXT   = 112;

}