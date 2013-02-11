<?php
/**
 * This class represents a websocket frame
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Socket;

/**
 * This class represents a websocket frame
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class Frame
{
    const OP_CONT  = 0x00;
    const OP_TEXT  = 0x01;
    const OP_BIN   = 0x02;

    const OP_CLOSE = 0x08;
    const OP_PING  = 0x09;
    const OP_PONG  = 0x0A;

    /**
     * @var bool Whether the frame has the FIN bit set
     */
    private $fin;

    /**
     * @var int RSV bit field
     */
    private $rsv;

    /**
     * @var int Frame opcode
     */
    private $opcode;

    /**
     * @var bool Whether the frame has the MASK bit set
     */
    private $mask;

    /**
     * @var int Payload length
     */
    private $masked;

    /**
     * @var string Frame masking key
     */
    private $maskingKey;

    /**
     * @var string Frame payload
     */
    private $data = '';

    /**
     * @var int Length of the frame payload
     */
    private $dataLength = 0;

    /**
     * @var int Number of fragments 
     */
    private $fragmentCount = 0;

    /**
     * @var string Current fragment payload
     */
    private $currentFragmentData = '';

    /**
     * @var int Expected length of the current fragment payload
     */
    private $currentFragmentDataLength;

    /**
     * @var bool Whether the current fragment is complete
     */
    private $fragmentComplete = true;

    /**
     * @var bool $fin The new FIN value
     */
    public function setFin($fin)
    {
        $this->fin = (bool) $fin;
    }

    /**
     * @return bool The current FIN value
     */
    public function isFin()
    {
        return $this->fin;
    }

    /**
     * @var bool $rsv1 The new RSV1 value
     */
    public function setRsv1($rsv1)
    {
        $this->rsv &= $rsv1 ? 0b111 : 0b110;
    }

    /**
     * @return bool The current RSV1 value
     */
    public function isRsv1()
    {
        return (bool) $this->rsv & 0b001;
    }

    /**
     * @var bool $rsv2 The new RSV2 value
     */
    public function setRsv2($rsv2)
    {
        $this->rsv &= $rsv2 ? 0b111 : 0b101;
    }

    /**
     * @return bool The current RSV2 value
     */
    public function isRsv2()
    {
        return (bool) $this->rsv & 0b010;
    }

    /**
     * @var bool $rsv3 The new RSV3 value
     */
    public function setRsv3($rsv3)
    {
        $this->rsv &= $rsv3 ? 0b111 : 0b011;
    }

    /**
     * @return bool The current RSV3 value
     */
    public function isRsv3()
    {
        return (bool) $this->rsv & 0b100;
    }

    /**
     * @param int $opcode The new opcode
     */
    public function setOpcode($opcode)
    {
        if ($opcode < 0x00 || $opcode > 0x0F) {
            throw new \OutOfRangeException('Invalid opcode '.$opcode);
        }

        $this->opcode = (int) $opcode;
    }

    /**
     * @return int The current opcode
     */
    public function getOpcode()
    {
        return $this->opcode;
    }

    /**
     * @var bool $masked The new MASK value
     */
    public function setMasked($masked)
    {
        $this->masked = (bool) $masked;

        if ($masked && !isset($this->maskingKey))
    {
            $this->setMaskingKey();
        }
    }

    /**
     * @return bool The current MASK value
     */
    public function isMasked()
    {
        return $this->masked;
    }

    /**
     * @var string $key The new masking key
     */
    public function setMaskingKey($key = NULL)
    {
        if (isset($key)) {
            if (strlen($key) !== 4) {
                throw new \InvalidArgumentException('Masking key must be exactly 4 bytes');
            }
        } else {
            $key = pack('C*', mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        }

        $this->maskingKey = $key;
    }

    /**
     * @return string The current masking key
     */
    public function getMaskingKey()
    {
        return $this->maskingKey;
    }

    /**
     * @param string $data The new data payload
     */
    public function setData($data)
    {
        $this->data = (string) $data;
        $this->dataLength = strlen($data);
    }

    /**
     * @return string The current data payload
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int The length of the current data payload
     */
    public function getDataLength()
    {
        return $this->dataLength;
    }

    /**
     * @return bool Whether the current fragment is complete
     */
    public function getFragmentCount()
    {
        return $this->fragmentCount;
    }

    /**
     * Decode a raw websocket frame into object properties
     *
     * @param string $data The encoded string
     * @throws \RangeException
     * @throws \WebSocketServer\Socket\NewControlFrameException
     * @throws \WebSocketServer\Socket\NewNonControlFrameException
     */
    public function fromRawData($data)
    {
        if ($this->fragmentComplete) {
            $this->fragmentComplete = false;
            $this->fragmentCount++;

            $this->removeAndParseFrameHeader($data);
        }

        $this->unmaskAndStoreFragmentData($data);
        $recievedLength = strlen($this->fragmentData);

        if ($recievedLength > $this->currentFragmentLength) {
            throw new \RangeException('Invalid Frame: Recieved payload length exceeds length stated in fragment header');
        } else if ($recievedLength === $this->currentFragmentLength) {
            $this->fragmentComplete = true;
            $this->data .= $this->fragmentData;
            $this->fragmentData = '';
            $this->currentFragmentLength = 0;

            if ($this->fin) {
                $this->dataLength = strlen($this->data);
            }
        }
    }

    /**
     * Encode object properties to a raw websocket frame
     *
     * @return string The encoded string
     */
    public function toRawData()
    {
        $firstByte = $secondByte = 0x00;
        $payloadLength = strlen($this->data);
        $lengthBody = $maskingKey = '';
        $payload = $this->data;

        if ($payloadLength > 0xFFFF) {
            $lengthHeader = 0x7F;
            $lengthBody = "\x00\x00\x00\x00".pack('N', $payloadLength); // This limits data to 4.3GB, fix plz
        } elseif ($payloadLength > 0x7D) {
            $lengthHeader = 0x7E;
            $lengthBody = pack('n', $payloadLength);
        } else {
            $lengthHeader = $payloadLength;
        }

        $firstByte |= ((int) $this->fin) << 7;
        $firstByte |= $this->rsv << 4;
        $firstByte |= $this->opcode;
        $firstByte = chr($firstByte);

        $secondByte |= ((int) $this->masked) << 7;
        $secondByte |= $lengthHeader;
        $secondByte = chr($secondByte);

        if ($this->masked) {
            $maskingKey = $this->maskingKey;
            $payload ^= str_pad('', $payloadLength, $maskingKey, STR_PAD_RIGHT); // This is memory hungry, fix pls
        }

        return $firstByte . $secondByte . $lengthBody . $maskingKey . $payload;
    }

    /**
     * Decode header of a raw websocket frame into object properties
     *
     * @param string $data The encoded string
     * @throws \RangeException
     * @throws \WebSocketServer\Socket\NewControlFrameException
     * @throws \WebSocketServer\Socket\NewNonControlFrameException
     */
    private function removeAndParseFrameHeader(&$data)
    {
        $firstByte = ord($data[0]);
        $secondByte = ord($data[1]);
        $data = substr($data, 2);

        $opcode = $firstByte & 0b00001111;
        $isControlFrame = $opcode & 0b00001000;
        if (isset($this->opcode)) {
            // Ugh, exceptions for flow control. Yes, I know, I'm shit. But it works.
            if ($isControlFrame) {
                throw new NewControlFrameException('The client sent a control frame between data frame fragments');
            } else if ($opcode) {
                throw new NewNonControlFrameException('The client started a new data frame between data frame fragments');
            }
        } else {
            $this->opcode = $opcode;
        }

        $this->fin = (bool) ($firstByte & 0b10000000);
        if (!$this->fin && $isControlFrame) {
            throw new \RangeException('Invalid Frame: Control frames cannot be fragmented');
        }

        $this->rsv = ($firstByte & 0b01110000) >> 4;

        $this->masked = (bool) ($secondByte & 0b10000000);
        $lengthHeader = $secondByte & 0b01111111;

        if ($lengthHeader === 0x7F) {
            if (ord($data[0]) & 0x80) {
                throw new \RangeException('Invalid Frame: Most significant bit of 64-bit length field set');
            }

            $this->currentFragmentLength = current(unpack('N', substr($data, 4, 4)));
            $data = substr($data, 8);
        } elseif ($lengthHeader === 0x7E) {
            $this->currentFragmentLength = current(unpack('n', substr($data, 0, 2)));
            $data = substr($data, 2);
        } else {
            $this->currentFragmentLength = $lengthHeader;
        }

        if ($this->masked) {
            $this->maskingKey = substr($data, 0, 4);
            $data = substr($data, 4);
        }
    }

    /**
     * Unmask a data string using the current masking key
     *
     * @param string $data The masked data
     * @return string The unmasked data
     */
    private function unmaskAndStoreFragmentData($data)
    {
        if ($this->masked) {
            $mask = $this->maskingKey;
            if (isset($this->fragmentData)) {
                $offset = (strlen($this->fragmentData) % 4) * -1;
                $mask = substr($this->maskingKey, $offset);
            }
            $mask = str_pad($mask, strlen($data), $this->maskingKey, STR_PAD_RIGHT);

            $data ^= $mask;
        }

        $this->fragmentData .= $data;
    }

}