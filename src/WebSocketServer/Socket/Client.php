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

use \WebSocketServer\Event\EventEmitter,
    \WebSocketServer\Event\EventFactory,
    \WebSocketServer\Core\Server,
    \WebSocketServer\Http\RequestFactory,
    \WebSocketServer\Http\ResponseFactory,
    \WebSocketServer\Log\Loggable;

/**
 * This class represents a client
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Client implements EventEmitter
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
     * @var \WebSocketServer\Event\EventFactory Event factory
     */
    private $eventFactory;

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
     * @var array[] Collection of registered event handlers
     */
    private $eventHandlers = [];

    /**
     * @var string Data waiting to be written to socket
     */
    private $pendingWriteBuffer = '';

    /**
     * Build the instance of the socket client
     *
     * @param resource                              $socket          The socket the client uses
     * @param \WebSocketServer\Core\Server          $server          The server to which this client belongs
     * @param \WebSocketServer\Event\EventFactory   $eventHandler    The event handler
     * @param \WebSocketServer\Http\RequestFactory  $requestFactory  Factory which http request objects
     * @param \WebSocketServer\Http\ResponseFactory $responseFactory Factory which http response objects
     * @param \WebSocketServer\Socket\FrameFactory  $frameFactory    Frame factory
     * @param \WebSocketServer\Log\Loggable         $logger          The logger
     */
    public function __construct(
        $socket,
        Server $server,
        EventFactory $eventFactory,
        RequestFactory $requestFactory,
        ResponseFactory $responseFactory,
        FrameFactory $frameFactory,
        Loggable $logger = null
    ) {
        $this->socket = $socket;
        $this->id     = (int) $socket;

        $this->server          = $server;
        $this->eventFactory    = $eventFactory;
        $this->requestFactory  = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->frameFactory    = $frameFactory;
        $this->logger          = $logger;
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
     * Fetch pending data from the wire
     *
     * @param int Maximum length of data to fetch
     * @param int recv() flags
     * @return string The fetched data
     */
    private function readData()
    {
        $data = fread($this->socket, 2048);

        if ($data === false) {
            $this->log('Data read failed', Loggable::LEVEL_ERROR);
            $this->trigger('error', $this, 'Data read failed');

            $this->disconnect();
        } else if ($data !== '') {
            return $data;
        }
    }

    /**
     * Queue frame data to be written to socket and trigger a write cycle
     *
     * @param \WebSocketServer\Socket\Frame $frame The frame to be sent
     */
    private function writeFrame($frame)
    {
        $this->pendingWriteBuffer .= $frame->toRawData();

        $this->processWrite();
    }

    /**
     * Perform the handshake when a new client tries to connect
     *
     * @param string The request from the client
     */
    private function shakeHands($buffer)
    {
        $this->log('Starting handshake process');
        $this->log("Client data: \n" . $buffer, Loggable::LEVEL_DEBUG);

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

        fwrite($this->socket, $responseString, strlen($responseString));

        $this->handshakeComplete = true;

        $this->log('Shaking hands with client');
        $this->log("Data sent to client: \n" . $responseString, Loggable::LEVEL_DEBUG);
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

            $this->trigger('frame', $this, $frame);

            if ($frame->isFin()) {
                $this->log('Client #' . $this->id . ' received message');
                $this->log('Message data: ' . $frame->getData(), Loggable::LEVEL_DEBUG);

                $this->trigger('message', $this, $frame);
            } else {
                $this->log('Client #' . $this->id . ' received partial message', Loggable::LEVEL_DEBUG);
            }
        } catch (\Exception $e) {
            $this->log($e->getMessage(), Loggable::LEVEL_ERROR);
            $this->trigger('error', $this, $e->getMessage());

            $this->disconnect();
        }
    }

    /**
     * Process pending data to be read from socket
     */
    public function processRead()
    {
        if (feof($this->socket)) {
            $this->disconnect();
        } else {
            $data = $this->readData();

            if (isset($data)) {
                if (!$this->handshakeComplete) {
                    $this->shakeHands($data);
                } else {
                    $this->processMessage($data);
                }
            }
        }
    }

    /**
     * Process pending data to be read from socket
     */
    public function processWrite()
    {
        $bytesWritten = fwrite($this->socket, $this->pendingWriteBuffer);

        if ($bytesWritten === false) {
            $this->log('Data write failed', Loggable::LEVEL_ERROR);
            $this->trigger('error', $this, 'Data write failed');

            $this->disconnect();
        } else if ($bytesWritten > 0) {
            $this->pendingWriteBuffer = (string) substr($this->pendingWriteBuffer, $bytesWritten);
        }
    }

    /**
     * Determine whether the client has data waiting to be written
     *
     * @return bool Whether the client has data waiting to be written
     */
    public function hasPendingWrite()
    {
        return (bool) strlen($this->pendingWriteBuffer);
    }

    /**
     * Disconnect client
     *
     * @param string $message The message to be send
     */
    public function disconnect($closeMessage = '')
    {
        if ($this->isConnected()) {
            if ($this->didHandshake()) {
                $this->sendClose($closeMessage);
            }

            fclose($this->socket);
            $this->socket = null;            

            $this->trigger('disconnect', $this);

            if ($this->server->getClientById($this->id)) {
                $this->server->removeClient($this);
            }
        }
    }

    /**
     * Determine whether the client socket is connected
     *
     * @return bool Whether the client socket is connected
     */
    public function isConnected()
    {
        return (bool) $this->socket;
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

    /**
     * Send a text message to a specific client
     *
     * @param string $message The message to be send
     */
    public function sendText($message)
    {
        $frame = $this->frameFactory->create();

        $frame->setData($message);
        $frame->setOpcode(Frame::OP_TEXT);
        $frame->setFin(true);

        $this->log('Sending message to client #' . $this->id);
        $this->log('Message data: ' . $message, Loggable::LEVEL_DEBUG);

        $this->writeFrame($frame);
    }

    /**
     * Send a close message to a specific client
     *
     * @param string $message The message to be send
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

        $this->log('Sending close frame to client #' . $this->id);
        $this->log('Message data: ' . $message, Loggable::LEVEL_DEBUG);

        $this->writeFrame($frame);
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