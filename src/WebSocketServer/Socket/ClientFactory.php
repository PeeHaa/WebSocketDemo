<?php
/**
 * This factory builds client sockets
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Socket;

use WebSocketServer\Socket\Client;

/**
 * This factory builds client sockets
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class ClientFactory
{
    /**
     * Build the instance of the socket client
     *
     * @param string   $id     The unique identifier for this client
     * @param resource $socket The socket the client uses
     *
     * @return \WebSocketServer\Socket\Client New instance of a socket client
     */
    public function create($id, $socket)
    {
        return new Client($id, $socket);
    }
}