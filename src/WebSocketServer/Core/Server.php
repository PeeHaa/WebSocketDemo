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

use \WebSocketServer\Event\EventEmitter,
    \WebSocketServer\Event\EventEmitters,
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
    use EventEmitters;

    /**
     * @var \WebSocketServer\Socket\ClientFactory Factory which builds client socket objects
     */
    private $clientFactory;

    /**
     * @var \WebSocketServer\Log\Loggable The logger
     */
    private $logger;

    /**
     * @var resource The master socket
     */
    private $master;

    /**
     * @var string The protocol to bind the socket to
     */
    private $bindProtocol;

    /**
     * @var string The IP address and port to bind the socket to
     */
    private $bindAddress;

    /**
     * @var int The \STREAM_CRYPTO_METHOD_* constant used for enabling security
     */
    private $securityMethod;

    /**
     * @var resource Stream context for sockets
     */
    private $socketContext;

    /**
     * @var resource[] List of all the open sockets
     */
    private $sockets = [];

    /**
     * @var \WebSocketServer\Socket\Client[] List of all the connected clients
     */
    private $clients = [];

    /**
     * @var bool Whether the server is running
     */
    private $running = false;

    /**
     * Build the server object
     *
     * @param \WebSocketServer\Socket\ClientFactory $clientFactory Factory which builds client socket objects
     * @param \WebSocketServer\Log\Loggable         $logger        The logger
     */
    public function __construct(ClientFactory $clientFactory, Loggable $logger = null)
    {
        $this->clientFactory = $clientFactory;
        $this->logger        = $logger;

        $this->socketContext = stream_context_create();
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
            $read = $this->sockets;
            $write = $this->getPendingWriteSockets();
            $except = $tv_sec = null;

            stream_select($read, $write, $except, $tv_sec);

            foreach ($read as $socket) {
                if ($socket == $this->master) {
                    $this->addClient();
                } else {
                    $this->clients[(int) $socket]->processRead();
                }
            }

            foreach ((array) $write as $socket) {
                $this->clients[(int) $socket]->processWrite();
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
        $address = "tcp://{$this->bindAddress}";
        $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
        $socket = stream_socket_server($address, $errNo, $errStr, $flags, $this->socketContext);

        if ($socket === false) {
            throw new \RuntimeException('Failed to create the master socket: ' . $errNo . ': ' . $errStr);
        }

        $this->sockets[(int) $socket] = $this->master = $socket;

        $this->log('Listening on ' . $this->getBindAddress());
        $this->log('Master socket: #' . ((int) $socket), Loggable::LEVEL_DEBUG);

        $this->trigger('listening', $this);
    }

    /**
     * Destroy the master socket
     */
    private function destroyMasterSocket()
    {
        $id = (int) $this->master;
        fclose($this->master);
        unset($this->master, $this->sockets[$id]);

        $this->trigger('close', $this);
    }

    /**
     * Accept a new client socket
     *
     * @return resource The new client socket
     */
    private function acceptClientSocket()
    {
        $socket = stream_socket_accept($this->master);

        if ($socket) {
            stream_set_blocking($socket, 0);

            if ($this->securityMethod) {
                $this->log('Beginning crypto negotiation');

                if (stream_socket_enable_crypto($socket, true, $this->securityMethod) === false) {
                    $this->log('stream_socket_enable_crypto() failed: ' . $this->getLastSocketError($socket), Loggable::LEVEL_WARN);

                    @fclose($socket);
                    $socket = false;
                }
            }
        } else {
            $this->log('stream_socket_accept() failed: ' . $this->getLastSocketError($this->master), Loggable::LEVEL_WARN);
        }

        return $socket;
    }

    /**
     * Get the last error from a socket
     *
     * @param resource $stream The stream socket resource
     *
     * @return string The error string
     */
    private function getLastSocketError($stream) {
        $errStr = '-1: Unknown error';

        if (function_exists('socket_import_stream')) {
            $socket = socket_import_stream($stream);
            $errCode = socket_last_error($socket);
            $errStr = $errCode . ': ' . socket_strerror($errCode);
        }

        return $errStr;
    }

    /**
     * Add a new client
     */
    private function addClient()
    {
        $socket = $this->acceptClientSocket();

        if ($socket) {
            $client = $this->clientFactory->create($socket, $this->securityMethod, $this);
            $this->clients[$client->getId()] = $client;
            $this->sockets[$client->getId()] = $socket;

            $this->trigger('clientconnect', $client);

            $this->log('Added client: ' . ((int) $socket));
        }
    }

    /**
     * Get an array of client sockets with data waiting to be written
     *
     * @return array The array of sockets
     */
    private function getPendingWriteSockets()
    {
        $result = [];

        foreach ($this->sockets as $socket) {
            if ($socket !== $this->master && $this->clients[(int) $socket]->hasPendingWrite()) {
                $result[] = $socket;
            }
        }

        return $result ?: null;
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
            ^(?:(?P<prot>tcp|ssl(?:v(?:2|3|23))?|tls)://)? # Protocol
            (?P<addr>
                (?:\[?[0-9a-f:]+\]?)                       # IPv6 address
              | (?:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})     # IPv4 address
            )
            :(?P<port>\d{1,5})                             # Port 
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
        
        if (!empty($parts['prot'])) {
            $protocol = strtolower($parts['prot']);

            if ($protocol !== 'tcp' && !in_array('openssl', get_loaded_extensions())) {
                throw new \InvalidArgumentException('Socket protocol wrapper ' . $protocol . ' not available on this system');
            }
        } else {
            $protocol = 'tcp';
        }

        switch ($protocol) {
            case 'tls':
                $securityMethod = \STREAM_CRYPTO_METHOD_TLS_SERVER;
                break;

            case 'sslv2':
                $securityMethod = \STREAM_CRYPTO_METHOD_SSLv2_SERVER;
                break;

            case 'sslv3':
                $securityMethod = \STREAM_CRYPTO_METHOD_SSLv3_SERVER;
                break;

            case 'ssl':
            case 'sslv23':
                $securityMethod = \STREAM_CRYPTO_METHOD_SSLv23_SERVER;
                break;

            default:
                $securityMethod = 0;
                break;
        }

        $this->bindProtocol   = $protocol;
        $this->bindAddress    = $address . ':' . $port;
        $this->securityMethod = $securityMethod;
    }

    /**
     * Get the bind address for the master socket
     *
     * @return string The bind address
     */
    public function getBindAddress()
    {
        if (isset($this->bindProtocol, $this->bindAddress)) {
            return "{$this->bindProtocol}://{$this->bindAddress}";
        }
    }

    /**
     * Set the bind address for the master socket
     *
     * @param string $address The bind address
     *
     * @throws \LogicException           When attempting to set the address while the server is running
     * @throws \InvalidArgumentException When $address is not a valid socket address
     */
    public function setSocketContextOption($wrapper, $optName, $value)
    {
        // This method is a bit of a "make it work" half-job
        // TODO: make this better

        $target = $this->running ? $this->socket : $this->socketContext;

        stream_context_set_option($target, $wrapper, $optName, $value);
    }

    /**
     * Start the server
     *
     * @param string $address The bind address
     *
     * @throws \LogicException When attempting to start the server while it is already running
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
     *
     * @throws \LogicException When attempting to stop the server while it is not running
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
     * @param string $message The message to be sent
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
     * @param string                         $message The message to be sent
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
}