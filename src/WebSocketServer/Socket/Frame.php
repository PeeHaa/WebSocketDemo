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
class Frame implements Writable
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
     * @var string The masking key used to encode the data
     */
    private $maskingKey;

    /**
     * @var int Length of the frame payload
     */
    private $dataLength;

    /**
     * @var string Frame payload
     */
    private $data;

    /**
     * Build the instance of Frame
     *
     * @param bool   $fin        Whether the frame has the FIN bit set
     * @param int    $rsv        The RSV bitfield for the frame
     * @param int    $opcode     The opcode for the frame
     * @param string $data       The frame data payload
     * @param string $maskingKey The key used to mask the data payload
     */
    public function __construct($fin, $rsv, $opcode, $data, $maskingKey = null)
    {
        $this->fin = (bool) $fin;
        $this->rsv = (int) $rsv;
        $this->opcode = (int) $opcode;
        $this->data = (string) $data;
        $this->maskingKey = $maskingKey;
        $this->dataLength = strlen($data);
    }

    /**
     * Get whether the frame FIN bit is set
     *
     * @return bool Whether the frame FIN bit is set
     */
    public function isFin()
    {
        return $this->fin;
    }

    /**
     * Get whether the frame RSV1 bit is set
     *
     * @return bool Whether the frame RSV1 bit is set
     */
    public function isRsv1()
    {
        return (bool) $this->rsv & 0b001;
    }

    /**
     * Get whether the frame RSV2 bit is set
     *
     * @return bool Whether the frame RSV2 bit is set
     */
    public function isRsv2()
    {
        return (bool) $this->rsv & 0b010;
    }

    /**
     * Get whether the frame RSV3 bit is set
     *
     * @return bool Whether the frame RSV3 bit is set
     */
    public function isRsv3()
    {
        return (bool) $this->rsv & 0b100;
    }

    /**
     * Get the opcode
     *
     * @return int The opcode
     */
    public function getOpcode()
    {
        return $this->opcode;
    }

    /**
     * Get the masking key
     *
     * @return string The masking key
     */
    public function getMaskingKey()
    {
        return $this->maskingKey;
    }

    /**
     * Get the length of the current data payload
     *
     * @return int The length of the current data payload
     */
    public function getDataLength()
    {
        return $this->dataLength;
    }

    /**
     * Get the data payload
     *
     * @return string The data payload
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Encode object properties to a raw websocket frame
     *
     * @return string The encoded string
     */
    public function toRawData()
    {
        if ($this->dataLength > 0xFFFF) {
            $lengthHeader = 0x7F;
            $lengthBody = "\x00\x00\x00\x00".pack('N', $this->dataLength); // This limits data to 4.3GB, fix plz
        } elseif ($this->dataLength > 0x7D) {
            $lengthHeader = 0x7E;
            $lengthBody = pack('n', $this->dataLength);
        } else {
            $lengthHeader = $this->dataLength;
            $lengthBody = '';
        }

        $firstByte = 0x00;
        $firstByte |= ((int) $this->fin) << 7;
        $firstByte |= $this->rsv << 4;
        $firstByte |= $this->opcode;

        $secondByte = 0x00;
        $secondByte |= ((int) isset($this->maskingKey)) << 7;
        $secondByte |= $lengthHeader;

        $firstWord = chr($firstByte) . chr($secondByte);


        if (isset($this->maskingKey)) {
            $maskingKey = $this->maskingKey;
            $payload = $this->data ^ str_pad('', $this->dataLength, $maskingKey, STR_PAD_RIGHT); // This is memory hungry, fix pls
        } else {
            $maskingKey = '';
            $payload = $this->data;
        }

        return $firstWord . $lengthBody . $maskingKey . $payload;
    }
}