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
     * Build the instance of the socket client
     *
     * @param resource                       $socket The socket the client uses
     * @param \WebSocketServer\Core\Server   $id     The unique identifier for this client
     *
     * @return \WebSocketServer\Socket\Client New instance of a socket client
     */
    public function create()
    {
        return new Frame();
    }
}