<?php

namespace WSSC\Components;

use WSSC\Contracts\CommonsContract;
use WSSC\Contracts\ConnectionContract;
use WSSC\Contracts\WebSocketServerContract;

/**
 * Class Connection
 * @package WSSC\Components
 */
class Connection implements ConnectionContract, CommonsContract
{
    /**
     * @var false|resource
     */
    private $socketConnection;

    /**
     * @var array
     */
    private array $clients;

    /**
     * Connection constructor.
     *
     * @param $sockConn
     * @param array $clients
     */
    public function __construct($sockConn, array $clients = [])
    {
        $this->socketConnection = $sockConn;
        $this->clients = $clients;
    }

    /**
     * Closes clients socket stream
     *
     * @throws \Exception
     */
    public function close(): void
    {
        if (is_resource($this->socketConnection)) {
            fwrite($this->socketConnection, $this->encode('', self::EVENT_TYPE_CLOSE));
            fclose($this->socketConnection);
        }
    }

    /**
     * This method is invoked when user implementation call $conn->send($data)
     * writes data to the clients stream socket
     *
     * @param string $data pure decoded data from server
     * @throws \Exception
     */
    public function send(string $data): void
    {
        fwrite($this->socketConnection, $this->encode($data));
    }

    /**
     * @param string $data data to send to clients
     * @throws \Exception
     */
    public function broadCast(string $data): void
    {
        foreach ($this->clients as $client) {
            if (is_resource($client)) { // check if not yet closed/broken etc
                fwrite($client, $this->encode($data));
            }
        }
    }

    /**
     * Broadcasting many messages with delay
     *
     * @param array $data An array of messages (strings) sent to many clients
     * @param int $delay Time in seconds to delay between messages
     * @throws \Exception
     */
    public function broadCastMany(array $data, int $delay = 0): void
    {
        foreach ($data as $message) {
            foreach ($this->clients as $client) {
                if (is_resource($client)) { // check if not yet closed/broken etc
                    fwrite($client, $this->encode($message));
                }
            }

            if ($delay > 0) {
                sleep($delay);
            }
        }
    }

    /**
     * Encodes data before writing to the client socket stream
     *
     * @param string $payload
     * @param string $type
     * @param boolean $masked
     * @return mixed
     * @throws \Exception
     */
    private function encode($payload, string $type = self::EVENT_TYPE_TEXT, bool $masked = false)
    {
        $frameHead = $this->getOpType($type);
        $payloadLength = strlen($payload);

        // set mask and payload length (using 1, 3 or 9 bytes)
        if ($payloadLength > self::PAYLOAD_MAX_BITS) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), self::PAYLOAD_CHUNK);
            $frameHead[1] = ($masked === true) ? self::MASK_255 : self::MASK_127;

            for ($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }

            // most significant bit MUST be 0
            if ($frameHead[2] > self::MASK_127) {
                return [
                    'type' => $type,
                    'payload' => $payload,
                    'error' => WebSocketServerContract::ERR_FRAME_TOO_LARGE,
                ];
            }
        } elseif ($payloadLength > self::MASK_125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), self::PAYLOAD_CHUNK);
            $frameHead[1] = ($masked === true) ? self::MASK_254 : self::MASK_126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + self::MASK_128 : $payloadLength;
        }

        return $this->getComposedFrame($frameHead, $payload, $payloadLength, $masked);
    }

    /**
     * Gets frame-head based on type of operation
     *
     * @param string $type Types of operation encode-frames
     * @return array
     */
    private function getOpType(string $type): array
    {
        $frameHead = [];

        switch ($type) {
            case self::EVENT_TYPE_TEXT:
                // first byte indicates FIN, Text-Frame (10000001):
                $frameHead[0] = self::ENCODE_TEXT;
                break;

            case self::EVENT_TYPE_CLOSE:
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = self::ENCODE_CLOSE;
                break;

            case self::EVENT_TYPE_PING:
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = self::ENCODE_PING;
                break;

            case self::EVENT_TYPE_PONG:
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = self::ENCODE_PONG;
                break;
        }

        return $frameHead;
    }

    /**
     * @param array $frameHead
     * @param string $payload
     * @param int $payloadLength
     * @param bool $masked
     * @return string
     * @throws \Exception
     */
    private function getComposedFrame(array $frameHead, string $payload, int $payloadLength, bool $masked): string
    {
        // convert frame-head to string:
        foreach (array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }

        // generate a random mask:
        $mask = [];
        if ($masked === true) {
            for ($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(random_int(0, self::MASK_255));
            }

            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);

        // append payload to frame:
        for ($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        return $frame;
    }

    /**
     * Gets unique socket id from resource
     *
     * @return int
     */
    public function getUniqueSocketId(): int
    {
        return (int)$this->socketConnection;
    }

    /**
     *  Gets client socket address host/port or UNIX path
     *
     * @return string
     */
    public function getPeerName(): string
    {
        return stream_socket_get_name($this->socketConnection, true);
    }
}
