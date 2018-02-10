<?php

namespace WSSC\Contracts;

/**
 *
 * @author Arthur Kushman
 */
interface CommonsContract
{

    // DADA types
    const EVENT_TYPE_PING = 'ping';
    const EVENT_TYPE_PONG = 'pong';
    const EVENT_TYPE_TEXT = 'text';
    const EVENT_TYPE_CLOSE = 'close';
    const EVENT_TYPE_BINARY = 'binary';
    const EVENT_TYPE_CONTINUATION = 'continuation';

    const MAP_EVENT_TYPE_TO_METHODS = [
        self::EVENT_TYPE_TEXT => 'onMessage',
        self::EVENT_TYPE_PING => 'onPing',
        self::EVENT_TYPE_PONG => 'onPong',
    ];

    // DECODE FRAMES
    const DECODE_TEXT = 1;
    const DECODE_BINARY = 2;
    const DECODE_CLOSE = 8;
    const DECODE_PING = 9;
    const DECODE_PONG = 10;
    // ENCODE FRAMES
    const ENCODE_TEXT = 129;
    const ENCODE_CLOSE = 136;
    const ENCODE_PING = 137;
    const ENCODE_PONG = 138;
    // MASKS
    const MASK_125 = 125;
    const MASK_126 = 126;
    const MASK_127 = 127;
    const MASK_128 = 128;
    const MASK_254 = 254;
    const MASK_255 = 255;
    // PAYLOADS
    const PAYLOAD_CHUNK = 8;
    const PAYLOAD_MAX_BITS = 65535;
}