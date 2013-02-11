<?php
/**
 * This class represents a client
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

/**
 * This class represents a client
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Client
{
    /**
     * @var string The unique identifier for this client
     */
    private $id;

    /**
     * @var resource The socket this client uses
     */
    private $socket;

    /**
     * @var boolean Whether the client already performed the handshake
     */
    private $handshake = false;

    /**
     * Build the instance of the socket client
     *
     * @param string   $id     The unique identifier for this client
     * @param resource $socket The socket the client uses
     */
    public function __construct($id, $socket)
    {
        $this->id     = $id;
        $this->socket = $socket;
    }

    /**
     * Get the socket of the client
     *
     * @return \WebSocketServer\Socket\Resource The socket the client uses
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Check whether this client performed the handshake
     *
     * @return boolean True when the client performed the handshake
     */
    public function didHandshake()
    {
        return $this->handshake === true;
    }

    /**
     * Set whether this client performed the handshake
     *
     * @param boolean $handshake The new value of the handshake flag
     */
    public function setHandshake($handshake)
    {
        $this->handshake = $handshake;
    }

    /**
     * Test whether the supplied socket is this client's socket
     *
     * @param resource $socket The socket to compare with the client's socket
     *
     * @return boolean True when the supplied socket matches with the client's socket
     */
    public function doesSocketMatch($socket)
    {
        return $this->socket == $socket;
    }
}