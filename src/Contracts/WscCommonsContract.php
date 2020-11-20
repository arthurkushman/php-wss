<?php

namespace WSSC\Contracts;

/**
 *
 * @author Arthur Kushman
 */
interface WscCommonsContract
{
    public const TCP_SCHEME = 'tcp://';

    public const MAX_BYTES_READ             = 65535;
    public const DEFAULT_TIMEOUT            = 5;
    public const DEFAULT_FRAGMENT_SIZE      = 4096;
    public const DEFAULT_RESPONSE_HEADER    = 8192;
    public const SEC_WEBSOCKET_ACCEPT_PTTRN = '/Sec-WebSocket-Accept:\s(.*)$/mUi';
    public const SERVER_KEY_ACCEPT          = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    public const PROXY_MATCH_RESP           = '/^HTTP\/\d\.\d 200/';

    // MASKS
    public const MASK_125 = 125;
    public const MASK_126 = 126;
    public const MASK_127 = 127;
    public const MASK_128 = 128;
    public const MASK_254 = 254;
    public const MASK_255 = 255;

    public const KEY_GEN_LENGTH = 16;
}
