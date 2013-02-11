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

use \WebSocketServer\Core\Server,
    \WebSocketServer\Event\Handler as EventHandler,
    \WebSocketServer\Log\Loggable,
    \WebSocketServer\Http\RequestFactory,
    \WebSocketServer\Http\ResponseFactory;

/**
 * This class represents a client
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Client
{
    const SIGNING_KEY = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    /**
     * @var int The unique identifier for this client, derived from the socket resource
     */
    private $id;

    /**
     * @var resource The socket this client uses
     */
    private $socket;

    /**
     * @var \WebSocketServer\Core\Server The server to which this client belongs
     */
    private $server;

    /**
     * @var \WebSocketServer\Event\Handler Event handler
     */
    private $eventHandler;

    /**
     * @var \WebSocketServer\Log\Loggable The logger
     */
    private $logger;

    /**
     * @var \WebSocketServer\Http\RequestFactory Factory which http request objects
     */
    private $requestFactory;

    /**
     * @var \WebSocketServer\Http\ResponseFactory Factory which http response objects
     */
    private $responseFactory;

    /**
     * @var \WebSocketServer\Socket\FrameFactory Frame factory
     */
    private $frameFactory;

    /**
     * @var boolean Whether the client already performed the handshake
     */
    private $handshakeComplete = false;

    /**
     * @var array A temporary store of an outstanding frame
     */
    private $pendingDataFrame;

    /**
     * Build the instance of the socket client
     *
     * @param resource                              $socket          The socket the client uses
     * @param \WebSocketServer\Core\Server          $server          The server to which this client belongs
     * @param \WebSocketServer\Event\Handler        $eventHandler    The event handler
     * @param \WebSocketServer\Log\Loggable         $logger          The logger
     * @param \WebSocketServer\Http\RequestFactory  $requestFactory  Factory which http request objects
     * @param \WebSocketServer\Http\ResponseFactory $responseFactory Factory which http response objects
     * @param \WebSocketServer\Socket\FrameFactory  $frameFactory    Frame factory
     */
    public function __construct(
        $socket,
        Server $server,
        EventHandler $eventHandler,
        Loggable $logger,
        RequestFactory $requestFactory,
        ResponseFactory $responseFactory,
        FrameFactory $frameFactory
    ) {
        $this->socket = $socket;
        $this->id     = (int) $socket;

        $this->server          = $server;
        $this->eventHandler    = $eventHandler;
        $this->logger          = $logger;
        $this->requestFactory  = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->frameFactory    = $frameFactory;
    }

    /**
     * Perform the handshake when a new client tries to connect
     *
     * @param string The request from the client
     */
    private function shakeHands($buffer)
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

        socket_write($this->socket, $responseString, strlen($responseString));

        $this->handshakeComplete = true;

        $this->logger->write('Shaking hands with client');
        $this->logger->write("Data sent to client: \n" . $responseString);
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
     * Process incoming message
     *
     * @param string The message
     */
    private function processMessage($message)
    {
        try {
            try {
                $frame = isset($this->pendingDataFrame) ? $this->pendingDataFrame : $this->frameFactory->create();
                $frame->fromRawData($message);

                if (!$frame->isFin()) {
                    $this->pendingDataFrame = $frame;
                } else {
                    unset($this->pendingDataFrame);
                }
            } catch (NewControlFrameException $e) {
                $frame = $this->frameFactory->create();
                $frame->fromRawData($message);
            }

            if ($frame->isFin()) {
                $this->logger->write('Client #' . $this->id . ' received message: ' . $frame->getData());

                $this->eventHandler->onMessage($this->server, $this, $frame);
            } else {
                $this->logger->write('Client #' . $this->id . ' received partial message');
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            $this->eventHandler->onError($this->server, $this, $e->getMessage());

            $this->disconnect();
        }
    }

    /**
     * Fetch pending data from the wire
     *
     * @param int Maximum length of data to fetch
     * @param int recv() flags
     * @return string The fetched data
     */
    private function fetchPendingData($length = 2048, $flags = 0)
    {
        if (@socket_recv($this->socket, $buffer, $length, $flags) > 0) {
            return $buffer;
        }
    }

    /**
     * Process pending data on socket
     */
    public function processRead()
    {
        $data = $this->fetchPendingData();

        if (!isset($data)) {
            $this->disconnect();
        } elseif (!$this->handshakeComplete) {
            $this->shakeHands($data);
        } else {
            $this->processMessage($data);
        }
    }

    /**
     * Disconnect client
     */
    public function diconnect() {
        $this->server->disconnectClient($this);
    }

    /**
     * Get the server to which this client belongs
     *
     * @return \WebSocketServer\Core\Server The server to which this client belongs
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Get the socket of the client
     *
     * @return resource The socket the client uses
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Get the ID of the client
     *
     * @return int The ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Check whether this client performed the handshake
     *
     * @return boolean True when the client performed the handshake
     */
    public function didHandshake()
    {
        return $this->handshakeComplete;
    }

    private function sendFrame($frame) {
        $data = $frame->toRawData();
        socket_write($this->socket, $data, strlen($data));
    }

    /**
     * Send a text message to a specific client
     *
     * @param string                         $message The message to be send
     * @param \WebSocketServer\Socket\Client $client  The client to send the message to
     */
    public function sendText($message)
    {
        $frame = $this->frameFactory->create();

        $frame->setData($message);
        $frame->setOpcode(Frame::OP_TEXT);
        $frame->setFin(true);

        $this->logger->write('Sending message to client #' . $this->id . ': ' . $message);
        $this->sendFrame($frame);
    }

    /**
     * Send a close message to a specific client
     *
     * @param string                         $message The message to be send
     * @param \WebSocketServer\Socket\Client $client  The client to send the message to
     */
    public function sendClose($message = '')
    {
        $frame = $this->frameFactory->create();

        if (strlen($message) > 125) {
            $message = substr($message, 0, 125);
        }

        $frame->setData($message);
        $frame->setOpcode(Frame::OP_CLOSE);
        $frame->setFin(true);

        $this->logger->write('Sending close frame to client #' . $this->id . ': ' . $message);
        $this->sendFrame($frame);
    }
}