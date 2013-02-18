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
class Message
{
    const OP_CONT  = 0x00;
    const OP_TEXT  = 0x01;
    const OP_BIN   = 0x02;

    const OP_CLOSE = 0x08;
    const OP_PING  = 0x09;
    const OP_PONG  = 0x0A;

    /**
     * @var int Length of the frame payload
     */
    private $dataLength;

    /**
     * @var \WebSocketServer\Socket\Frame[] Frames that make up this message
     */
    private $frames;

    /**
     * Get whether the message has the RSV1 bit set
     *
     * @return bool Whether the message has the RSV1 bit set
     */
    public function __construct(array $frames)
    {
        $this->frames = $frames;
    }

    /**
     * Get whether the message has the RSV1 bit set
     *
     * @return bool Whether the message has the RSV1 bit set
     */
    public function isRsv1()
    {
        return $this->frames[0]->isRsv1();
    }

    /**
     * Get whether the message has the RSV2 bit set
     *
     * @return bool Whether the message has the RSV2 bit set
     */
    public function isRsv2()
    {
        return $this->frames[0]->isRsv2();
    }

    /**
     * Get whether the message has the RSV3 bit set
     *
     * @return bool Whether the message has the RSV3 bit set
     */
    public function isRsv3()
    {
        return $this->frames[0]->isRsv3();
    }

    /**
     * Get the message opcode
     *
     * @return int The message opcode
     */
    public function getOpcode()
    {
        return $this->frames[0]->getOpcode();
    }

    /**
     * Get the data payload as a string
     *
     * @return string The data payload
     */
    public function getData()
    {
        $data = '';

        foreach ($this->frames as $frame) {
            $data .= $frame->getData();
        }

        return $data;
    }

    /**
     * Get the length of the current data payload
     *
     * @return int The length of the current data payload
     */
    public function getDataLength()
    {
        if (!isset($this->dataLength)) {
            $this->dataLength = 0;

            foreach ($this->frames as $frame) {
                $this->dataLength += $frame->getDataLength();
            }
        }

        return $this->dataLength;
    }

    /**
     * Get the number of frames that make up the message
     *
     * @return bool The number of frames that make up the message
     */
    public function getFrameCount()
    {
        return count($this->frames);
    }
}