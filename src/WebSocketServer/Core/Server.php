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

use \WebSocketServer\Event\Emitter as EventEmitter,
    \WebSocketServer\Event\EventFactory,
    \WebSocketServer\Socket\ClientFactory,
    \WebSocketServer\Socket\Client,
    \WebSocketServer\Log\Loggable;

/**
 * The actual websocket server
 *
 * @category   WebSocketServer
 * @package    Core
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Server implements EventEmitter
{
    /**
     * @var \WebSocketServer\Socket\ClientFactory Factory which builds client socket objects
     */
    private $clientFactory;

    /**
     * @var \WebSocketServer\Socket\EventFactory Factory which builds event objects
     */
    private $eventFactory;

    /**
     * @var \WebSocketServer\Log\Loggable The logger
     */
    private $logger;

    /**
     * @var resource The master socket
     */
    private $master;

    /**
     * @var string The base protocol to use for the socket (currently unused, always TCP)
     */
    private $bindProtocol;

    /**
     * @var string The IP address to bind the socket to
     */
    private $bindAddress;

    /**
     * @var int The port address to bind the socket to
     */
    private $bindPort;

    /**
     * @var resource[] List of all the open sockets
     */
    private $sockets = [];

    /**
     * @var \WebSocketServer\Socket\Client[] List of all the connected clients
     */
    private $clients = [];

    /**
     * @var array[] Collection of registered event handlers
     */
    private $eventHandlers = [];

    /**
     * @var bool Whether the server is running
     */
    private $running = false;

    /**
     * Build the server object
     *
     * @param \WebSocketServer\Socket\ClientFactory $clientFactory Factory which builds client socket objects
     * @param \WebSocketServer\Socket\EventFactory  $eventFactory  Factory which builds event objects
     * @param \WebSocketServer\Log\Loggable         $logger        The logger
     */
    public function __construct(EventFactory $eventFactory, ClientFactory $clientFactory, Loggable $logger = null)
    {
        $this->clientFactory = $clientFactory;
        $this->eventFactory  = $eventFactory;
        $this->logger        = $logger;
    }

    /**
     * Log a message to logger if defined
     *
     * @param string $message The message
     */
    private function log($message, $level = Loggable::LEVEL_INFO)
    {
        if (isset($this->logger)) {
            $this->logger->write($message, $level);
        }
    }

    /**
     * Main server loop
     *
     * @throws \LogicException   When bind address is not set
     * @throws \RuntimeException When setting up the socket fails
     */
    private function run()
    {
        $this->createMasterSocket();

        $this->log('Server started');
        $this->running = true;

        while ($this->running) {
            $read   = $this->sockets;
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

        foreach ($this->clients as $client) {
            $client->disconnect();
        }
        $this->destroyMasterSocket();

        $this->log('Server stopped');
    }

    /**
     * Create the master socket
     *
     * @throws \LogicException   When bind address is not set
     * @throws \RuntimeException When setting up the socket fails
     */
    private function createMasterSocket()
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($socket === false) {
            throw new \RuntimeException('Failed to create the master socket.');
        }

        if (socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1) === false) {
            throw new \RuntimeException('Failed to set the master socket options.');
        }

        if (socket_bind($socket, $this->bindAddress, $this->bindPort) === false) {
            throw new \RuntimeException('Failed to bind the master socket.');
        }

        if (socket_listen($socket) === false) {
            throw new \RuntimeException('Failed to listen on master socket.');
        }

        $this->sockets[(int) $socket] = $this->master = $socket;

        $this->log('Listening on   : ' . $this->getBindAddress());
        $this->log('Master socket  : #' . ((int) $socket), Loggable::LEVEL_DEBUG);

        $this->trigger('listening', $this);
    }

    /**
     * Destroy the master socket
     */
    private function destroyMasterSocket()
    {
        $id = (int) $this->master;
        socket_close($this->master);
        unset($this->master, $this->sockets[$id]);

        $this->trigger('close', $this);
    }

    /**
     * Accept a new client socket
     */
    private function acceptClientSocket()
    {
        $socket = socket_accept($this->master);
        if (!$socket) {
            $errCode = socket_last_error($this->master);
            $errStr = socket_strerror($errCode);
            $this->log('socket_accept() failed: '.$errCode.': '.$errStr, Loggable::LEVEL_WARN);
        }
        return $socket;
    }

    /**
     * Add a new client
     */
    private function addClient()
    {
        $socket = $this->acceptClientSocket();

        if ($socket) {
            $client = $this->clientFactory->create($socket, $this);
            $this->clients[$client->getId()] = $client;
            $this->sockets[$client->getId()] = $socket;

            $this->trigger('clientconnect', $client);

            $this->log('Added client: ' . ((int) $socket));
        }
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
        return $this->getClientById((int) $socket);
    }

    /**
     * Set the bind address for the master socket
     *
     * @param string $address The bind address
     *
     * @throws \LogicException           When attempting to set the address while the server is running
     * @throws \InvalidArgumentException When $address is not a valid socket address
     */
    public function setBindAddress($address)
    {
        if ($this->isRunning()) {
            throw new \LogicException('Cannot change bind address while the server is running');
        }

        $expr = '@
            ^(?:(?P<prot>tcp|ssl(?:v(?:2|3))?|tls)://)? # Protocol
            (?P<addr>
                (?:\[?[0-9a-f:]+\]?)                    # IPv6 address
              | (?:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})  # IPv4 address
            )
            :(?P<port>\d{1,5})                          # Port 
        @ix';
        $match = preg_match($expr, $address, $parts);

        if (!$match) {
            throw new \InvalidArgumentException('Socket address in an invalid format');
        }

        $parts['addr'] = strtolower(trim($parts['addr'], '[]'));
        if (filter_var($parts['addr'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $address = $parts['addr'];
        } else if (filter_var($parts['addr'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $address = '['.$parts['addr'].']';
        } else {
            throw new \InvalidArgumentException('IP portion of socket address is invalid');
        }

        $port = (int) $parts['port'];
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException('Port portion of socket address is invalid');
        }
        
        $protocol = !empty($parts['prot']) ? strtolower($parts['prot']) : 'tcp';

        $this->bindProtocol = $protocol;
        $this->bindAddress  = $address;
        $this->bindPort     = $port;
    }

    /**
     * Get the bind address for the master socket
     *
     * @return string The bind address
     */
    public function getBindAddress()
    {
        if (isset($this->bindProtocol, $this->bindAddress, $this->bindPort)) {
            return "{$this->bindProtocol}://{$this->bindAddress}:$this->bindPort";
        }
    }

    /**
     * Start the server
     *
     * @param string $address The bind address
     */
    public function start($address = null)
    {
        if ($this->running) {
            throw new \LogicException('Cannot start server, already running');
        }

        if (isset($address)) {
            $this->setBindAddress($address);
        }

        $this->log('Starting server');

        $this->run();
    }

    /**
     * Stop the server
     */
    public function stop()
    {
        if (!$this->running) {
            throw new \LogicException('Cannot stop server, not running');
        }

        $this->log('Stopping server');

        $this->running = false;
    }

    /**
     * Determine whether the server is running
     *
     * @return bool Whether the server is running
     */
    public function isRunning()
    {
        return $this->running;
    }

    /**
     * Get a client based on id
     *
     * @param int $id The id of the client to return
     *
     * @return \WebSocketServer\Socket\Client The found client
     */
    public function getClientById($id)
    {
        if (isset($this->clients[$id])) {
            return $this->clients[$id];
        }
    }

    /**
     * Disconnect client
     *
     * @param \WebSocketServer\Socket\Client $client The client to disconnect
     *
     * @throws \OutOfRangeException When passed client does not belong to this server
     */
    public function removeClient(Client $client)
    {
        if ($client->getServer() !== $this) {
            throw new \OutOfRangeException('Client does not belong to this server');
        }

        if ($client->isConnected()) {
            $client->disconnect();
        }

        $id = $client->getId();
        if (isset($this->clients[$id])) {
            unset($this->clients[$id], $this->sockets[$id]);

            $this->trigger('clientremove', $this, $client);
        }
    }

    /**
     * Send a message to all clients connected
     *
     * @param string $message The message to be send
     */
    public function broadcast($message)
    {
        $this->log('Broadcasting message to all clients: ' . $message);

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
        $this->log('Broadcasting message to all clients but #' . $skipClient->getId() . ': ' . $message);

        foreach ($this->clients as $client) {
            if ($client === $skipClient) {
                continue;
            }

            $client->sendText($message);
        }
    }

    /**
     * Register an event handler callback
     *
     * @param string   $eventName The event name
     * @param callable $callback  The event handler
     */
    public function on($eventName, callable $callback)
    {
        if (!isset($this->eventHandlers[$eventName])) {
            $this->eventHandlers[$eventName] = [];
        }

        $this->eventHandlers[$eventName][] = $callback;
    }

    /**
     * Unregister a single event handler callback or all handlers for an event
     *
     * @param string   $eventName The event name
     * @param callable $callback  The event handler
     */
    public function off($eventName, callable $callback = null)
    {
        if (isset($this->eventHandlers[$eventName])) {
            if (isset($callback)) {
                $key = array_search($callback, $this->eventHandlers[$eventName], true);
                if ($key !== false) {
                    array_splice($this->eventHandlers[$eventName], $key, 1);
                }
            } else {
                $this->eventHandlers[$eventName] = [];
            }
        }
    }

    /**
     * Trigger an event
     *
     * @param string $eventName The event name
     * @param mixed  $arg,...   Arguments passed to the event handler
     */
    public function trigger($eventName)
    {
        if (isset($this->eventHandlers[$eventName])) {
            $args = func_get_args();
            array_shift($args);

            $event = $this->eventFactory->create($this, $eventName, $args);
            array_unshift($args, $event);

            foreach ($this->eventHandlers[$eventName] as $handler) {
                $result = call_user_func_array($handler, $args);

                if ($result === false || $event->isContinuationStopped()) {
                    break;
                }
            }
        }
    }
}