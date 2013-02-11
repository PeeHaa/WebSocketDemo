<?php
/**
 * The actual websocket server
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Core
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Core;

use \WebSocketServer\Event\Handler as EventHandler,
    \WebSocketServer\Log\Loggable,
    \WebSocketServer\Socket\Clientfactory,
    \WebSocketServer\Socket\Client;

/**
 * The actual websocket server
 *
 * @category   WebSocketServer
 * @package    Core
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Server
{
    /**
     * @var \WebSocketServer\Event\Handler The event handler
     */
    private $eventHandler;

    /**
     * @var \WebSocketServer\Log\Loggable The logger
     */
    private $logger;

    /**
     * @var \WebSocketServer\Socket\ClientFactory Factory which builds client socket objects
     */
    private $clientFactory;

    /**
     * @var resource The mast socket
     */
    private $master;

    /**
     * @var array List of all the open sockets
     */
    private $sockets = [];

    /**
     * @var \WebSocketServer\Socket\Client[] List of all the connected clients
     */
    private $clients = [];

    /**
     * Build the server object
     *
     * @param \WebSocketServer\Event\Handler        $eventHandler    The event handler
     * @param \WebSocketServer\Log\Loggable         $logger          The logger
     * @param \WebSocketServer\Socket\ClientFactory $clientFactory   Factory which builds client socket objects
     */
    public function __construct(EventHandler $eventHandler, Loggable $logger, ClientFactory $clientFactory)
    {
        $this->eventHandler  = $eventHandler;
        $this->logger        = $logger;
        $this->clientFactory = $clientFactory;
    }

    /**
     * Create the master socket
     *
     * @param string $address The address to listen on
     * @param int    $port    The port to listen on
     *
     * @throws \RuntimeException When setting up the socket fails
     */
    private function setMasterSocket($address, $port)
    {
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($this->master === false) {
            throw new \RuntimeException('Failed to create the master socket.');
        }

        if (socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1) === false) {
            throw new \RuntimeException('Failed to set the master socket options.');
        }

        if (socket_bind($this->master, $address, $port) === false) {
            throw new \RuntimeException('Failed to bind the master socket.');
        }

        if (socket_listen($this->master) === false) {
            throw new \RuntimeException('Failed to listen.');
        }

        $this->sockets[(int) $this->master] = $this->master;

        $this->logger->write('Server Started : ' . (new \DateTime())->format('Y-m-d H:i:s'));
        $this->logger->write('Listening on   : ' . $address . ':' . $port);
        $this->logger->write('Master socket  : ' . $this->master . "\n");
    }

    /**
     * Add a new client
     *
     * @param resource $socket The client socket
     */
    private function addClient()
    {
        $socket = socket_accept($this->master);
        if (!$socket) {
            $errCode = socket_last_error($this->master);
            $errStr = socket_strerror($errCode);
            $this->logger->write('socket_accept() failed: '.$errCode.': '.$errStr);
        }

        $client = $this->clientFactory->create($socket, $this, $this->eventHandler);
        $this->clients[$client->getId()] = $client;
        $this->sockets[$client->getId()] = $socket;

        $this->eventHandler->onConnect($this, $client);

        $this->logger->write('Added client: ' . $socket);
    }

    /**
     * Get a client based on socket
     *
     * @param resource $socket The socket of which to find the client
     *
     * @return \WebSocketServer\Socket\Client The found client
     */
    private function getClientBySocket($socket)
    {
        $id = (int) $socket;

        if (isset($this->clients[$id])) {
            return $this->clients[$id];
        }
    }

    /**
     * Disconnect client
     *
     * @param \WebSocketServer\Socket\Client $client The client to disconnect
     * @throws \OutOfRangeException
     */
    public function disconnectClient(Client $client)
    {
        $id = $client->getId();

        if (!isset($this->clients[$id], $this->sockets[$id])) {
            throw new \OutOfRangeException('Client does not belong to this server');
        }

        $this->eventHandler->onDisconnect($this, $client);

        if ($client->didHandshake()) {
            $client->sendClose();
        }

        socket_close($client->getSocket());
        unset($this->clients[$id], $this->sockets[$id]);

        $this->logger->write('Disconnected client: ' . $socket);
    }

    /**
     * Start and run the server
     *
     * @param string $address The address to listen on
     * @param int    $port    The port to listen on
     */
    public function start($address, $port)
    {
        $this->setMasterSocket($address, $port);

        while(true) {
            $read = $this->sockets;
            $write  = null;
            $except = null;
            $tv_sec = null;

            socket_select($read, $write, $except, $tv_sec);

            foreach ($read as $socket) {
                if ($socket == $this->master) {
                    $this->addClient();
                } else {
                    $client = $this->getClientBySocket($socket);
                    $client->processRead();
                }
            }
        }
    }

    /**
     * Send a message to all clients connected
     *
     * @param string $message The message to be send
     */
    public function broadcast($message)
    {
        $this->logger->write('Broadcasting message to all clients: ' . $message);

        foreach ($this->clients as $client) {
            $client->sendText($message);
        }
    }

    /**
     * Send a message to all client but one
     *
     * @param string                         $message The message to be send
     * @param \WebSocketServer\Socket\Client $client  The client NOT to send the message to
     */
    public function sendToAllButClient($message, $skipClient)
    {
        $this->logger->write('Broadcasting message to all clients but #' . $skipClient->getId() . ': ' . $message);

        foreach ($this->clients as $client) {
            if ($client === $skipClient) {
                continue;
            }

            $client->sendText($message);
        }
    }
}