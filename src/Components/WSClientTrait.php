<?php

namespace WSSC\Components;

use WSSC\Contracts\CommonsContract;
use WSSC\Exceptions\BadOpcodeException;
use WSSC\Exceptions\BadUriException;
use WSSC\Exceptions\ConnectionException;

trait WSClientTrait
{
    /**
     * Validates whether server sent valid upgrade response
     *
     * @param ClientConfig $config
     * @param string $pathWithQuery
     * @param string $key
     * @throws ConnectionException
     */
    private function validateResponse(ClientConfig $config, string $pathWithQuery, string $key)
    {
        $response = stream_get_line($this->socket, self::DEFAULT_RESPONSE_HEADER, "\r\n\r\n");
        if (!preg_match(self::SEC_WEBSOCKET_ACCEPT_PTTRN, $response, $matches)) {
            $address = $config->getScheme() . '://' . $config->getHost() . ':' . $config->getPort() . $pathWithQuery;
            throw new ConnectionException(
                "Connection to '{$address}' failed: Server sent invalid upgrade response:\n"
                . $response, CommonsContract::CLIENT_INVALID_UPGRADE_RESPONSE
            );
        }

        $keyAccept = trim($matches[1]);
        $expectedResponse = base64_encode(pack('H*', sha1($key . self::SERVER_KEY_ACCEPT)));
        if ($keyAccept !== $expectedResponse) {
            throw new ConnectionException('Server sent bad upgrade response.',
                CommonsContract::CLIENT_INVALID_UPGRADE_RESPONSE);
        }
    }

    /**
     *  Gets host uri based on protocol
     *
     * @param ClientConfig $config
     * @return string
     * @throws BadUriException
     */
    private function getHostUri(ClientConfig $config): string
    {
        if (in_array($config->getScheme(), ['ws', 'wss'], true) === false) {
            throw new BadUriException(
                "Url should have scheme ws or wss, not '{$config->getScheme()}' from URI '$this->socketUrl' .",
                CommonsContract::CLIENT_INCORRECT_SCHEME
            );
        }

        return ($config->getScheme() === 'wss' ? 'ssl' : 'tcp') . '://' . $config->getHost();
    }

    /**
     * @param string $data
     * @return float|int
     * @throws ConnectionException
     */
    private function getPayloadLength(string $data)
    {
        $payloadLength = (int)ord($data[1]) & self::MASK_127; // Bits 1-7 in byte 1
        if ($payloadLength > self::MASK_125) {
            if ($payloadLength === self::MASK_126) {
                $data = $this->read(2); // 126: Payload is a 16-bit unsigned int
            } else {
                $data = $this->read(8); // 127: Payload is a 64-bit unsigned int
            }
            $payloadLength = bindec(self::sprintB($data));
        }

        return $payloadLength;
    }

    /**
     * @param string $data
     * @param int $payloadLength
     * @return string
     * @throws ConnectionException
     */
    private function getPayloadData(string $data, int $payloadLength): string
    {
        // Masking?
        $mask = (bool)(ord($data[1]) >> 7);  // Bit 0 in byte 1
        $payload = '';
        $maskingKey = '';

        // Get masking key.
        if ($mask) {
            $maskingKey = $this->read(4);
        }

        // Get the actual payload, if any (might not be for e.g. close frames.
        if ($payloadLength > 0) {
            $data = $this->read($payloadLength);

            if ($mask) {
                // Unmask payload.
                for ($i = 0; $i < $payloadLength; $i++) {
                    $payload .= ($data[$i] ^ $maskingKey[$i % 4]);
                }
            } else {
                $payload = $data;
            }
        }

        return $payload;
    }

    /**
     * @return null|string
     * @throws \WSSC\Exceptions\BadOpcodeException
     * @throws \InvalidArgumentException
     * @throws BadOpcodeException
     * @throws BadUriException
     * @throws ConnectionException
     * @throws \Exception
     */
    protected function receiveFragment(): ?string
    {
        // Just read the main fragment information first.
        $data = $this->read(2);

        // Is this the final fragment?  // Bit 0 in byte 0
        /// @todo Handle huge payloads with multiple fragments.
        $final = (bool)(ord($data[0]) & 1 << 7);

        // Parse opcode
        $opcodeInt = ord($data[0]) & 31; // Bits 4-7
        $opcodeInts = array_flip(self::$opcodes);
        if (!array_key_exists($opcodeInt, $opcodeInts)) {
            throw new ConnectionException("Bad opcode in websocket frame: $opcodeInt",
                CommonsContract::CLIENT_BAD_OPCODE);
        }

        $opcode = $opcodeInts[$opcodeInt];

        // record the opcode if we are not receiving a continutation fragment
        if ($opcode !== 'continuation') {
            $this->lastOpcode = $opcode;
        }

        $payloadLength = $this->getPayloadLength($data);
        $payload = $this->getPayloadData($data, $payloadLength);

        if ($opcode === CommonsContract::EVENT_TYPE_CLOSE) {
            // Get the close status.
            if ($payloadLength >= 2) {
                $statusBin = $payload[0] . $payload[1];
                $status = bindec(sprintf('%08b%08b', ord($payload[0]), ord($payload[1])));
                $this->closeStatus = $status;
                $payload = substr($payload, 2);

                if (!$this->isClosing) {
                    $this->send($statusBin . 'Close acknowledged: ' . $status,
                        CommonsContract::EVENT_TYPE_CLOSE); // Respond.
                }
            }

            if ($this->isClosing) {
                $this->isClosing = false; // A close response, all done.
            }

            fclose($this->socket);
            $this->isConnected = false;
        }

        if (!$final) {
            $this->hugePayload .= $payload;

            return NULL;
        } // this is the last fragment, and we are processing a huge_payload

        if ($this->hugePayload) {
            $payload = $this->hugePayload .= $payload;
            $this->hugePayload = NULL;
        }

        return $payload;
    }

    /**
     * @param $final
     * @param $payload
     * @param $opcode
     * @param $masked
     * @throws ConnectionException
     * @throws \Exception
     */
    protected function sendFragment($final, $payload, $opcode, $masked)
    {
        // Binary string for header.
        $frameHeadBin = '';
        // Write FIN, final fragment bit.
        $frameHeadBin .= (bool)$final ? '1' : '0';
        // RSV 1, 2, & 3 false and unused.
        $frameHeadBin .= '000';
        // Opcode rest of the byte.
        $frameHeadBin .= sprintf('%04b', self::$opcodes[$opcode]);
        // Use masking?
        $frameHeadBin .= $masked ? '1' : '0';

        // 7 bits of payload length...
        $payloadLen = strlen($payload);
        if ($payloadLen > self::MAX_BYTES_READ) {
            $frameHeadBin .= decbin(self::MASK_127);
            $frameHeadBin .= sprintf('%064b', $payloadLen);
        } else if ($payloadLen > self::MASK_125) {
            $frameHeadBin .= decbin(self::MASK_126);
            $frameHeadBin .= sprintf('%016b', $payloadLen);
        } else {
            $frameHeadBin .= sprintf('%07b', $payloadLen);
        }

        $frame = '';

        // Write frame head to frame.
        foreach (str_split($frameHeadBin, 8) as $binstr) {
            $frame .= chr(bindec($binstr));
        }
        // Handle masking
        if ($masked) {
            // generate a random mask:
            $mask = '';
            for ($i = 0; $i < 4; $i++) {
                $mask .= chr(random_int(0, 255));
            }
            $frame .= $mask;
        }

        // Append payload to frame:
        for ($i = 0; $i < $payloadLen; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        $this->write($frame);
    }
}