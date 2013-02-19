<?php
/**
 * Factory for data buffers
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
 * Factory for data buffers
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class DataBufferFactory
{
    /**
     * Create a new data buffer
     *
     * @return \WebSocketServer\Socket\DataBuffer The data buffer
     */
    public function create()
    {
        return new DataBuffer;
    }
}