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
    WebSocketServer\Socket\Frame\Encoder,
    WebSocketServer\Socket\Frame\Decoder,
    WebSocketServer\Core\Server,
    WebSocketServer\Socket\Client;

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
        $server->sendToAllButClient('A new user (' . $client->getSocket() . ') connected', $client);
    }

    /**
     * Callback when a client sends a message
     *
     * @param \WebSocketServer\Core\Server   $server  The websocket server
     * @param \WebSocketServer\Socket\Client $client  The client
     * @param string                         $message The message
     */
    public function onMessage(Server $server, Client $client, $message)
    {
        $server->broadcast('User (' . $client->getSocket() . '): ' . $message);
    }

    /**
     * Callback when a client disconnects
     *
     * @param \WebSocketServer\Core\Server   $server The websocket server
     * @param \WebSocketServer\Socket\Client $client The client
     */
    public function onDisconnect(Server $server, Client $client)
    {
        $server->sendToAllButClient('User (' . $client->getSocket() . ') disconnected', $client);
    }
}

/**
 * Start the server
 */
$eventHandler    = new EventHandler();
$logger          = new EchoOutput();
$clientFactory   = new ClientFactory();
$requestFactory  = new RequestFactory();
$responseFactory = new ResponseFactory();
$frameEncoder    = new Encoder(new Queue());
$frameDecoder    = new Decoder(new Queue());
$socketServer    = new Server($eventHandler, $logger, $clientFactory, $requestFactory, $responseFactory, $frameEncoder, $frameDecoder);

$socketServer->start('127.0.0.1', 1337);