<?php
/**
 * This factory builds frames
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
 * This factory builds frames
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class FrameFactory
{
    /**
     * Build the instance of Frame
     *
     * @param bool   $fin    Whether the frame has the FIN bit set
     * @param int    $rsv    The RSV bitfield for the frame
     * @param int    $opcode The opcode for the frame
     * @param string $data   The frame data payload
     *
     * @return \WebSocketServer\Socket\Frame New instance of a socket client
     */
    public function create($fin, $rsv, $opcode, $data)
    {
        return new Frame($fin, $rsv, $opcode, $data);
    }
}