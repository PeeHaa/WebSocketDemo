<?php
/**
 * Decodes raw data from the network into Frame and Message objects
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
 * Decodes raw data from the network into Frame and Message objects
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
     * @param \WebSocketServer\Socket\FrameFactory   $frameFactory   Frame factory object
     */
    public function __construct(FrameFactory $frameFactory)
    {
        $this->frameFactory = $frameFactory;
    }

    /**
     * Build the factory object
     *
     * @param \WebSocketServer\Socket\FrameFactory   $frameFactory   Frame factory object
     * @param \WebSocketServer\Socket\MessageFactory $messageFactory Message factory object
     */
    public function create()
    {
        return new MessageEncoder($this->frameFactory);
    }
}