<?php
/**
 * This factory builds messages
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
 * This factory builds messages
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class MessageFactory
{
    /**
     * Build the instance of Message
     *
     * @param Frame[] $frames The frames that make up the message
     *
     * @return \WebSocketServer\Socket\Message New instance of a socket client
     */
    public function create(array $frames)
    {
        return new Message($frames);
    }
}