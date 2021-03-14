<?php


namespace WSSC\Components;


use WSSC\Contracts\CommonsContract;
use WSSC\Contracts\WebSocketServerContract;

/**
 * Class WssMain
 *
 * @package WSSC\Components
 *
 * @property ServerConfig config
 */
class WssMain implements CommonsContract
{
    /**
     * @var bool
     */
    private bool $isPcntlLoaded = false;

    /**
     * Message frames decoder
     *
     * @param string $data
     * @return mixed null on empty data|false on improper data|array - on success
     */
    protected function decode(string $data)
    {
        if (empty($data)) {
            return null; // close has been sent
        }

        $unmaskedPayload = '';
        $decodedData = [];

        // estimate frame type:
        $firstByteBinary = sprintf('%08b', ord($data[0]));
        $secondByteBinary = sprintf('%08b', ord($data[1]));
        $isMasked = $secondByteBinary[0] === '1';
        $payloadLength = ord($data[1]) & self::MASK_127;

        // unmasked frame is received:
        if (!$isMasked) {
            return ['type' => '', 'payload' => '', 'error' => WebSocketServerContract::ERR_PROTOCOL];
        }

        $this->getTypeByOpCode($firstByteBinary, $decodedData);
        if (empty($decodedData['type'])) {
            return ['type' => '', 'payload' => '', 'error' => WebSocketServerContract::ERR_UNKNOWN_OPCODE];
        }

        $mask = substr($data, 2, 4);
        $payloadOffset = WebSocketServerContract::PAYLOAD_OFFSET_6;
        $dataLength = $payloadLength + $payloadOffset;
        if ($payloadLength === self::MASK_126) {
            $mask = substr($data, 4, 4);
            $payloadOffset = WebSocketServerContract::PAYLOAD_OFFSET_8;
            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
        } elseif ($payloadLength === self::MASK_127) {
            $mask = substr($data, 10, 4);
            $payloadOffset = WebSocketServerContract::PAYLOAD_OFFSET_14;
            $tmp = '';
            for ($i = 0; $i < 8; $i++) {
                $tmp .= sprintf('%08b', ord($data[$i + 2]));
            }
            $dataLength = bindec($tmp) + $payloadOffset;
            unset($tmp);
        }

        /**
         * We have to check for large frames here. socket_recv cuts at 1024 bytes
         * so if websocket-frame is > 1024 bytes we have to wait until whole
         * data is transferd.
         */
        if (strlen($data) < $dataLength) {
            return false;
        }

        for ($i = $payloadOffset; $i < $dataLength; $i++) {
            $j = $i - $payloadOffset;
            if (isset($data[$i])) {
                $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
            }
        }
        $decodedData['payload'] = $unmaskedPayload;

        return $decodedData;
    }

    /**
     * Returns true if pcntl ext loaded and false otherwise
     *
     * @return bool
     */
    protected function isPcntlLoaded(): bool
    {
        return $this->isPcntlLoaded;
    }

    /**
     * Sets pre-loaded pcntl state
     *
     * @param bool $isPcntlLoaded
     */
    protected function setIsPcntlLoaded(bool $isPcntlLoaded): void
    {
        $this->isPcntlLoaded = $isPcntlLoaded;
    }

    /**
     * Detects decode data type
     *
     * @param string $firstByteBinary
     * @param array $decodedData
     */
    private function getTypeByOpCode(string $firstByteBinary, array &$decodedData)
    {
        $opcode = bindec(substr($firstByteBinary, 4, 4));
        switch ($opcode) {
            // text frame:
            case self::DECODE_TEXT:
                $decodedData['type'] = self::EVENT_TYPE_TEXT;
                break;
            case self::DECODE_BINARY:
                $decodedData['type'] = self::EVENT_TYPE_BINARY;
                break;
            // connection close frame:
            case self::DECODE_CLOSE:
                $decodedData['type'] = self::EVENT_TYPE_CLOSE;
                break;
            // ping frame:
            case self::DECODE_PING:
                $decodedData['type'] = self::EVENT_TYPE_PING;
                break;
            // pong frame:
            case self::DECODE_PONG:
                $decodedData['type'] = self::EVENT_TYPE_PONG;
                break;
            default:
                $decodedData['type'] = '';
                break;
        }
    }

    /**
     * Checks if there are less connections for amount of processes
     * @param int $totalClients
     * @param int $maxClients
     */
    protected function lessConnThanProc(int $totalClients, int $maxClients): void
    {
        if ($totalClients !== 0 && $maxClients > $totalClients
            && $totalClients % $this->config->getClientsPerFork() === 0) {
            exit(1);
        }
    }

    /**
     * Clean socket resources that were closed,
     * thus avoiding (stream_select(): supplied resource is not a valid stream resource)
     * @param array $readSocks
     */
    protected function cleanSocketResources(array &$readSocks): void
    {
        foreach ($readSocks as $k => $sock) {
            if (!is_resource($sock)) {
                unset($readSocks[$k]);
            }
        }
    }
}
