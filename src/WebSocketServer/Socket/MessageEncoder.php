<?php
/**
 * Encodes data into frame objects
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
 * Encodes data into frame objects
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class MessageEncoder
{
    /**
     * @var \WebSocketServer\Socket\FrameFactory Frame factory object
     */
    private $frameFactory;

    /**
     * Build the message decoder object
     *
     * @param \WebSocketServer\Socket\FrameFactory   $frameFactory   Frame factory object
     */
    public function __construct(FrameFactory $frameFactory)
    {
        $this->frameFactory = $frameFactory;
    }

    /**
     * Build a frame from raw data
     *
     * @param string $data   The data string
     * @param int    $opcode The frame opcode
     * @param bool   $fin    True if the frame has the FIN bit set
     * @param int    $rsv    The RSV bit field for the frame
     *
     * @return WebSocketServer\Socket\Frame The created frame
     */
    public function encodeString($data, $opcode, $fin = true, $rsv = 0)
    {
        return $this->frameFactory->create($fin, $rsv, $opcode, $data);
    }

    /**
     * Generate 4 random non-null bytes
     *
     * @return string The generated string
     */
    private function generateMaskingKey()
    {
        return pack('C*', mt_rand(1, 255), mt_rand(1, 255), mt_rand(1, 255), mt_rand(1, 255));
    }

    /**
     * Calculate the XOR mask of the data and the key
     *
     * @param string $data The input data
     * @param string $key  The mask key
     *
     * @return string The masked string
     */
    private function maskData($data, $key)
    {
        return $data ^ str_pad($key, strlen($data), $key, STR_PAD_RIGHT);
    }
}