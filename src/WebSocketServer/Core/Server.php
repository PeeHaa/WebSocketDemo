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

use WebSocketServer\Event\Handler,
    WebSocketServer\Log\Loggable,
    WebSocketServer\Socket\Clientfactory,
    WebSocketServer\Http\RequestFactory,
    WebSocketServer\Http\ResponseFactory,
    WebSocketServer\Socket\Frame\Encoder,
    WebSocketServer\Socket\Frame\Decoder,
    WebSocketServer\Socket\Client;

/**
 * The actual websocket server
 *
 * @category   WebSocketServer
 * @package    Core
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Server
{
    const SIGNING_KEY = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

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
     * @var \WebSocketServer\Http\RequestFactory Factory which http request objects
     */
    private $requestFactory;

    /**
     * @var \WebSocketServer\Http\ResponseFactory Factory which http response objects
     */
    private $responseFactory;

    /**
     * @var \WebSocketServer\Socket\Frame\Encoder Socket frame encoder
     */
    private $encoder;

    /**
     * @var \WebSocketServer\Socket\Frame\Decoder Socket frame decoder
     */
    private $decoder;

    /**
     * @var resource The mast socket
     */
    private $master;

    /**
     * @var array List of all the open sockets
     */
    private $sockets = [];

    /**
     * Build the server object
     *
     * @param \WebSocketServer\Event\Handler        $eventHandler    The event handler
     * @param \WebSocketServer\Log\Loggable         $logger          The logger
     * @param \WebSocketServer\Socket\ClientFactory $clientFactory   Factory which builds client socket objects
     * @param \WebSocketServer\Http\RequestFactory  $requestFactory  Factory which http request objects
     * @param \WebSocketServer\Http\ResponseFactory $responseFactory Factory which http response objects
     * @param \WebSocketServer\Socket\Frame\Encoder $encoder         Socket frame encoder
     * @param \WebSocketServer\Socket\Frame\Decoder $decoder         Socket frame decoder
     */
    public function __construct(
        Handler $eventHandler,
        Loggable $logger,
        ClientFactory $clientFactory,
        RequestFactory $requestFactory,
        ResponseFactory $responseFactory,
        Encoder $encoder,
        Decoder $decoder
    ) {
        $this->eventHandler    = $eventHandler;
        $this->logger          = $logger;
        $this->clientFactory   = $clientFactory;
        $this->requestFactory  = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->encoder         = $encoder;
        $this->decoder         = $decoder;
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
            $changedSockets = $this->sockets;

            $write  = null;
            $except = null;
            $tv_sec = null;

            socket_select($changedSockets, $write, $except, $tv_sec);

            foreach ($changedSockets as $changedSocket) {
                if ($changedSocket == $this->master) {
                    $client = socket_accept($this->master);

                    if ($client < 0) {
                        $this->logger->write('socket_accept() failed');
                        continue;
                    }

                    $this->addClient($client);
                } elseif (@socket_recv($changedSocket, $buffer, 2048, 0) == 0) {
                    $this->disconnectClient($changedSocket);
                } else {
                    $client = $this->getClientBySocket($changedSocket);

                    if($client->didHandshake() === false) {
                        $this->shakeHands($client, $buffer);
                    } else{
                        $this->process($client, $buffer);
                    }
                }
            }
        }
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

        $this->addSocket($this->master);

        $this->logger->write('Server Started : ' . (new \DateTime())->format('Y-m-d H:i:s'));
        $this->logger->write('Listening on   : ' . $address . ':' . $port);
        $this->logger->write('Master socket  : ' . $this->master . "\n");
    }

    /**
     * Add a new client
     *
     * @param resource $socket The client socket
     */
    private function addClient($socket)
    {
        $this->clients[] = $this->clientFactory->create(uniqid(), $socket);
        $this->addSocket($socket);

        $this->eventHandler->onConnect($this, end($this->clients));

        $this->logger->write('Added client: ' . $socket);
    }

    /**
     * Add a new socket
     *
     * @param resource $socket The client socket
     */
    private function addSocket($socket)
    {
        $this->sockets[] = $socket;
    }

    /**
     * Disconnect client
     *
     * @param resource $socket The client socket to disconnect
     */
    private function disconnectClient($socket)
    {
        $found = null;
        foreach ($this->clients as $id => $client) {
            if ($client->getSocket() == $socket) {
                $found = $id;
                break;
            }
        }

        if (!is_null($found)) {
            $this->eventHandler->onDisconnect($this, $this->clients[$found]);

            array_splice($this->clients, $found, 1);
        }

        $index = array_search($socket, $this->sockets);

        socket_close($socket);

        $this->logger->write('Disconnected client: ' . $socket);

        if($index >= 0) {
            array_splice($this->sockets, $index, 1);
        }
    }

    /**
     * Process incoming messages
     *
     * @param \WebSocketServer\Socket\Client $client  The client from which we retrieved the message
     * @param string                         $message The message
     */
    private function process(Client $client, $message)
    {
        $this->logger->write('Received message: ' . $this->decoder->getDecodedMessage($message));

        $this->eventHandler->onMessage($this, $client, $this->decoder->getDecodedMessage($message));
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
        foreach($this->clients as $client) {
            if ($client->doesSocketMatch($socket) === true) {
                return $client;
            }
        }
    }

    /**
     * Perform the handshake when a new client tries to connect
     *
     * @param \WebSocketServer\Socket\Client $client The client trying to connect
     * @param string                         $buffer The request from the client
     */
    private function shakeHands(Client $client, $buffer)
    {
        $this->logger->write('Starting handshake process');
        $this->logger->write("Client data: \n" . $buffer);

        $request  = $this->requestFactory->create($buffer);
        $request->parse();
        $response = $this->responseFactory->create();

        $response->addHeader('HTTP/1.1 101 WebSocket Protocol Handshake');
        $response->addHeader('Upgrade', 'WebSocket');
        $response->addHeader('Connection', 'Upgrade');
        $response->addHeader('Sec-WebSocket-Origin', $request->getOrigin());
        $response->addHeader('Sec-WebSocket-Location', 'ws://' . $request->getHost() . $request->getResource());
        $response->addHeader('Sec-WebSocket-Accept', $this->getSignature($request->getKey()));

        $responseString = $response->buildResponse();

        socket_write($client->getSocket(), $responseString, strlen($responseString));

        $client->setHandshake(true);

        $this->logger->write('Shaking hands with client');
        $this->logger->write("Data sent to client: \n" . $response->buildResponse());
    }

    /**
     * Generate a signature to be used when shaking hands with the client
     *
     * @param string $key The key used to sign the response
     *
     * @return string The signture
     */
    private function getSignature($key)
    {
        return base64_encode(sha1($key . self::SIGNING_KEY, true));
    }

    /**
     * Send a message to all clients connected
     *
     * @param string $message The message to be send
     */
    public function broadcast($message)
    {
        $this->logger->write('Broadcasting message to all clients: ' . $this->encoder->getEncodedMessage($message));

        foreach ($this->clients as $client) {
            $this->sendToClient($message, $client);
        }
    }

    /**
     * Send a message to a specific client
     *
     * @param string                         $message The message to be send
     * @param \WebSocketServer\Socket\Client $client  The client to send the message to
     */
    public function sendToClient($message, $client)
    {
        $this->logger->write('Sending message to client (' . $client->getSocket() . '): ' . $this->encoder->getEncodedMessage($message));

        socket_write($client->getSocket(), $this->encoder->getEncodedMessage($message), strlen($this->encoder->getEncodedMessage($message)));
    }

    /**
     * Send a message to all client but one
     *
     * @param string                         $message The message to be send
     * @param \WebSocketServer\Socket\Client $client  The client to send the message NOT to
     */
    public function sendToAllButClient($message, $skipClient)
    {
        $this->logger->write('Broadcasting message to all clients but (' . $skipClient->getSocket() . '): ' . $this->encoder->getEncodedMessage($message));

        foreach ($this->clients as $client) {
            if ($client == $skipClient) {
                continue;
            }

            $this->sendToClient($message, $client);
        }
    }
}