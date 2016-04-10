<?php

namespace WSSC;

/**
 *
 * @author Arthur Kushman
 */
interface ICommons {

    // DADA types
    const EVENT_TYPE_PING = 'ping',
            EVENT_TYPE_PONG = 'pong',
            EVENT_TYPE_TEXT = 'text',
            EVENT_TYPE_CLOSE = 'close',
            EVENT_TYPE_BINARY = 'binary';
    // DECODE FRAMES
    const DECODE_TEXT = 1,
            DECODE_BINARY = 2,
            DECODE_CLOSE = 8,
            DECODE_PING = 9,
            DECODE_PONG = 10;
    // ENCODE FRAMES
    const ENCODE_TEXT = 129,
            ENCODE_CLOSE = 136,
            ENCODE_PING = 137,
            ENCODE_PONG = 138;
    // MASKS
    const MASK_125 = 125,
            MASK_126 = 126,
            MASK_127 = 127,
            MASK_128 = 128,
            MASK_254 = 254,
            MASK_255 = 255;
    // PAYLOADS
    const PAYLOAD_CHUNK = 8;
    const PAYLOAD_MAX_BITS = 65535;

}
