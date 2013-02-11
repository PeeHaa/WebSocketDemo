<?php

/**
 * Simple example of a chat server
 */

use WebSocketServer\Event\Handler,
    WebSocketServer\Log\EchoOutput,
    WebSocketServer\Socket\ClientFactory,
    WebSocketServer\Http\RequestFactory,
    WebSocketServer\Http\ResponseFactory,
    WebSocketServer\Cache\Queue,
    WebSocketServer\Socket\FrameFactory,
    WebSocketServer\Core\Server,
    WebSocketServer\Socket\Client,
    WebSocketServer\Socket\Frame;

// setup environment
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('date.timezone', 'Europe/Amsterdam');
set_time_limit(0);
ob_implicit_flush();

// load the lib
require __DIR__ . '/../src/WebSocketServer/bootstrap.php';

class EventHandler implements Handler
{
    /**
     * Callback when a client connects
     *
     * @param \WebSocketServer\Core\Server   $server The websocket server
     * @param \WebSocketServer\Socket\Client $client The client
     */
    public function onConnect(Server $server, Client $client)
    {
        $server->sendToAllButClient('User #' . $client->getId() . ' entered the room', $client);
    }

    /**
     * Callback when a client sends a message
     *
     * @param \WebSocketServer\Core\Server   $server  The websocket server
     * @param \WebSocketServer\Socket\Client $client  The client
     * @param \WebSocketServer\Socket\Frame  $frame The message
     */
    public function onMessage(Server $server, Client $client, Frame $frame)
    {
        $server->broadcast('#' . $client->getId() . ': ' . $frame->getData());
    }

    /**
     * Callback when a client disconnects
     *
     * @param \WebSocketServer\Core\Server   $server The websocket server
     * @param \WebSocketServer\Socket\Client $client The client
     */
    public function onDisconnect(Server $server, Client $client)
    {
        $server->sendToAllButClient('User #' . $client->getId() . ' left the room', $client);
    }

    /**
     * Callback when a client suffers an error
     *
     * @param \WebSocketServer\Core\Server   $server  The websocket server
     * @param \WebSocketServer\Socket\Client $client  The client
     * @param string                         $message The error description
     */
    public function onError(Server $server, Client $client, $message)
    {
        $server->sendToAllButClient('User #' . $client->getId() . ' fell over', $client);
    }
}

/**
 * Start the server
 */
$eventHandler    = new EventHandler();
$logger          = new EchoOutput();
$requestFactory  = new RequestFactory();
$responseFactory = new ResponseFactory();
$frameFactory    = new FrameFactory();
$clientFactory   = new ClientFactory($eventHandler, $logger, $requestFactory, $responseFactory, $frameFactory);
$socketServer    = new Server($eventHandler, $logger, $clientFactory);

$socketServer->start('0.0.0.0', 1337);