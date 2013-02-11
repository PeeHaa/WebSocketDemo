<?php
/**
 * Interface for the socket event handlers
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Event
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Event;

use WebSocketServer\Core\Server,
    WebSocketServer\Socket\Client;

/**
 * Interface for the socket event handlers
 *
 * @category   WebSocketServer
 * @package    Event
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
interface Handler
{
    /**
     * Callback when a client connects
     *
     * @param \WebSocketServer\Core\Server   $server The websocket server
     * @param \WebSocketServer\Socket\Client $client The client
     */
    public function onConnect(Server $server, Client $client);

    /**
     * Callback when a client sends a message
     *
     * @param \WebSocketServer\Core\Server   $server  The websocket server
     * @param \WebSocketServer\Socket\Client $client  The client
     * @param string                         $message The message
     */
    public function onMessage(Server $server, Client $client, $message);

    /**
     * Callback when a client disconnects
     *
     * @param \WebSocketServer\Core\Server   $server The websocket server
     * @param \WebSocketServer\Socket\Client $client The client
     */
    public function onDisconnect(Server $server, Client $client);
}