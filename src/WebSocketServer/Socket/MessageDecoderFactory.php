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
class MessageDecoderFactory
{
    /**
     * @var \WebSocketServer\Socket\FrameFactory Frame factory object
     */
    private $frameFactory;

    /**
     * @var \WebSocketServer\Socket\MessageFactory Message factory object
     */
    private $messageFactory;

    /**
     * Build the factory object
     *
     * @param \WebSocketServer\Socket\FrameFactory   $frameFactory   Frame factory object
     * @param \WebSocketServer\Socket\MessageFactory $messageFactory Message factory object
     */
    public function __construct(FrameFactory $frameFactory, MessageFactory $messageFactory)
    {
        $this->frameFactory = $frameFactory;
        $this->messageFactory = $messageFactory;
    }

    /**
     * Build the factory object
     *
     * @param \WebSocketServer\Socket\FrameFactory   $frameFactory   Frame factory object
     * @param \WebSocketServer\Socket\MessageFactory $messageFactory Message factory object
     */
    public function create()
    {
        return new MessageDecoder($this->frameFactory, $this->messageFactory);
    }
}