<?php

namespace WSSC\Contracts;

/**
 *
 * @author Arthur Kushman
 */
interface WscCommonsContract
{

    const MAX_BYTES_READ = 65535;
    const DEFAULT_TIMEOUT = 5;
    const DEFAULT_FRAGMENT_SIZE = 4096;
    const DEFAULT_RESPONSE_HEADER = 1024;
    const SEC_WEBSOCKET_ACCEPT_PTTRN = '/Sec-WebSocket-Accept:\s(.*)$/mUi';
    const SERVER_KEY_ACCEPT = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    const RESOURCE_TYPE_STREAM = '';
    // MASKS
    const MASK_125 = 125;
    const MASK_126 = 126;
    const MASK_127 = 127;
    const MASK_128 = 128;
    const MASK_254 = 254;
    const MASK_255 = 255;

    const KEY_GEN_LENGTH = 16;
}
