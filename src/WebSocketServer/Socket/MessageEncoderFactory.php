<?php
/**
 * Factory for message encoder objects
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
 * Factory for message encoder objects
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class MessageEncoderFactory
{
    /**
     * @var \WebSocketServer\Socket\FrameFactory Frame factory object
     */
    private $frameFactory;

    /**
     * Build the factory object
     *
     * @param \WebSocketServer\Socket\FrameFactory $frameFactory Frame factory object
     */
    public function __construct(FrameFactory $frameFactory)
    {
        $this->frameFactory = $frameFactory;
    }

    /**
     * Build the factory object
     *
     * @return \WebSocketServer\Socket\MessageEncoder The message encode object
     */
    public function create()
    {
        return new MessageEncoder($this->frameFactory);
    }
}