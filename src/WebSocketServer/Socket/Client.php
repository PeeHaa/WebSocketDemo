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
    \WebSocketServer\Event\EventEmitters,
    \WebSocketServer\Core\Server,
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
    use EventEmitters;

    const SEND_RSV1    = 0x01;
    const SEND_RSV2    = 0x02;
    const SEND_RSV3    = 0x04;
    const SEND_PARTIAL = 0x08;

    /**
     * @var int The unique identifier for this client, derived from the socket resource
     */
    private $id;

    /**
     * @var resource The socket this client uses
     */
    private $socket;

    /**
     * @var int The \STREAM_CRYPTO_METHOD_* constant used for enabling security
     */
    private $securityMethod;

    /**
     * @var \WebSocketServer\Core\Server The server to which this client belongs
     */
    private $server;

    /**
     * @var \WebSocketServer\Log\Loggable The logger
     */
    private $logger;

    /**
     * @var \WebSocketServer\Socket\Handshake Handshake object
     */
    private $handshake;

    /**
     * @var \WebSocketServer\Socket\Buffer Buffer object
     */
    private $buffer;

    /**
     * @var \WebSocketServer\Socket\FrameFactory Frame factory
     */
    private $frameFactory;

    /**
     * @var \WebSocketServer\Socket\MessageEncoder Message encoder object
     */
    private $messageEncoder;

    /**
     * @var \WebSocketServer\Socket\MessageDecoder Message decoder object
     */
    private $messageDecoder;

    /**
     * @var boolean Whether crypto negotiation on the socket is complete
     */
    private $cryptoComplete = false;

    /**
     * @var array A temporary store of an outstanding frame
     */
    private $pendingDataFrame;

    /**
     * @var array Data waiting to be written to socket
     */
    private $pendingWrites = [];

    /**
     * @var string Data waiting to be written to socket
     */
    private $pendingWriteBuffer = '';

    /**
     * @var array Additional data store for use by application
     */
    private $appData = [];

    /**
     * Build the instance of the socket client
     *
     * @param resource                               $socket         The socket the client uses
     * @param int                                    $securityMethod The \STREAM_CRYPTO_METHOD_* constant used for enabling security
     * @param \WebSocketServer\Core\Server           $server         The server to which this client belongs
     * @param \WebSocketServer\Socket\Handshake      $handshake      Handshake object
     * @param \WebSocketServer\Socket\Buffer         $buffer         Buffer object
     * @param \WebSocketServer\Socket\MessageEncoder $messageEncoder Message encoder object
     * @param \WebSocketServer\Socket\MessageDecoder $messageDecoder Message decoder object
     * @param \WebSocketServer\Log\Loggable          $logger         The logger
     */
    public function __construct(
        $socket,
        $securityMethod,
        Server $server,
        Handshake $handshake,
        Buffer $buffer,
        MessageEncoder $messageEncoder,
        MessageDecoder $messageDecoder,
        Loggable $logger = null
    ) {
        $this->socket         = $socket;
        $this->id             = (int) $socket;
        $this->securityMethod = $securityMethod;
        $this->server         = $server;

        $this->handshake      = $handshake;
        $this->buffer         = $buffer;
        $this->messageEncoder = $messageEncoder;
        $this->messageDecoder = $messageDecoder;
        $this->logger         = $logger;

        $messageDecoder->on('frame', function($event, Frame $frame) {
            $this->trigger('frame', $this, $frame);
        });

        $messageDecoder->on('message', function($event, Message $message) {
            $this->trigger('message', $this, $message);
            if ($message->getOpcode() === Message::OP_PING) {
                $this->sendPong($message);
            }
        });

        $messageDecoder->on('error', function($event, $message) {
            $this->log('Data decode failed: ' . $message, Loggable::LEVEL_ERROR);
            $this->trigger('error', $this, $message);

            $this->disconnect();
        });
    }

    /**
     * Log a message to logger if defined
     *
     * @param string $message The message
     * @param int    $level   The level of the message
     */
    private function log($message, $level = Loggable::LEVEL_INFO)
    {
        if (isset($this->logger)) {
            $this->logger->write($callerStr . $message, $level);
        }
    }

    /**
     * Get the last error from the client socket
     */
    private function getLastSocketError() {
        $errStr = '-1: Unknown error';

        if (function_exists('socket_import_stream')) {
            $socket = socket_import_stream($this->socket);
            $errCode = socket_last_error($socket);
            $errStr = $errCode . ': ' . socket_strerror($errCode);
        }

        return $errStr;
    }

    /**
     * Fetch pending data from the wire
     */
    private function readDataIntoBuffer()
    {
        $data = '';
        $read = [$this->socket];
        $write = $except = null;

        /* Note by DaveRandom:
         * This is a little odd. When I tested on Fedora, fread() was only returning
         * 1 byte at a time. Other systems (including another Fedora box) didn't do
         * this. This loop fixes the issue, and shouldn't negatively impact performance
         * too badly, as sane systems will only iterate once, all parsers are buffered.
         */
        do {
            $bytes = fread($this->socket, 8192);

            if ($bytes === false) {
                $this->log('Data read failed: ' . $this->getLastSocketError(), Loggable::LEVEL_ERROR);
                $this->trigger('error', $this, 'Data read failed');

                $this->disconnect();
                return;
            }

            $data .= $bytes;
        } while (stream_select($read, $write, $except, 0));

        $this->log('Got data from socket: ' . $data, Loggable::LEVEL_DEBUG);
        $this->buffer->write($data);
    }

    /**
     * Perform the handshake when a new client tries to connect
     */
    private function shakeHands()
    {
        $this->log('Shaking hands');
        $this->log("Buffer contents:\n" . $this->buffer, Loggable::LEVEL_DEBUG);

        try {
            $success = $this->handshake->readClientHandshake($this->buffer);
        } catch (\RangeException $e) {
            $this->log('Handshake failed: ' . $e->getMessage());
            $this->trigger('error', $this, $e->getMessage());

            $this->disconnect();
            return;
        }

        if ($success) {
            if (!$this->trigger('handshake', $this, $this->handshake->getRequest(), $this->handshake->getResponse())) {
                $this->log('Handshake rejected by event handler');

                $this->disconnect();
            } else {
                $response = $this->handshake->getServerHandshake();

                $this->pendingWrites[] = $response;
                $this->processWrite();

                $this->log('Handshake process complete');
                $this->log("Data sent to client: \n" . $response->toRawData(), Loggable::LEVEL_DEBUG);
            }
        }
    }

    /**
     * Convert a flag field to RSV bits
     *
     * @param int $flags A bitmask as flags
     *
     * @return int The RSV field
     */
    private function makeRSVField($flags)
    {
        return ((int) $flags) & 0b111;
    }

    /**
     * Queue an object for writing
     *
     * @param \WebSocketServer\Socket\Writable $writable The data to send
     */
    private function writeObject(Writable $writable)
    {
        $this->pendingWrites[] = $writable;
        $this->processWrite();
    }

    /**
     * Send a pong message to a specific client
     *
     * @param \WebSocketServer\Socket\Message $message The ping message being responded to
     */
    private function sendPong(Message $ping)
    {
        $this->log('Sending pong frame to client #' . $this->id);
        $this->log('Message data: ' . $ping->getData(), Loggable::LEVEL_DEBUG);

        $this->writeObject($this->messageEncoder->encodeString($ping->getData(), Frame::OP_PONG));
    }

    /**
     * Send a close message to a specific client
     *
     * @param string $message The message to be sent as the data payload
     */
    private function sendClose($message = '')
    {
        $this->log('Sending close frame to client #' . $this->id);
        $this->log('Message data: ' . $message, Loggable::LEVEL_DEBUG);

        $this->writeObject($this->messageEncoder->encodeString($message, Frame::OP_CLOSE));
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
     * Get the server to which this client belongs
     *
     * @return \WebSocketServer\Core\Server The server to which this client belongs
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Process pending data to be read from socket
     */
    public function processRead()
    {
        if (feof($this->socket)) {
            $this->log('Client closed remote socket', Loggable::LEVEL_WARN);
            $this->disconnect();
        } else if ($this->securityMethod && !$this->cryptoComplete) {
            $this->log('Continuing crypto negotiation');

            $success = stream_socket_enable_crypto($this->socket, true, $this->securityMethod);
            if ($success === false) {
                $this->log('stream_socket_enable_crypto() failed: ' . $this->getLastSocketError(), Loggable::LEVEL_WARN);
                $this->trigger('error', $this, 'stream_socket_enable_crypto() failed: ' . $this->getLastSocketError());

                $this->disconnect();
            } else if ($success) {
                $this->log('Crypto negotiation complete');
                $this->cryptoComplete = true;

                $this->trigger('cryptoenabled', $this);
            }
        } else {
            $this->readDataIntoBuffer();

            if (!$this->handshake->isComplete()) {
                $this->shakeHands();
            } else {
                $this->messageDecoder->processData($this->buffer);
            }
        }
    }

    /**
     * Process pending data to be written to socket
     */
    public function processWrite()
    {
        if (!$this->hasPendingWrite()) {
            return;
        }

        if (!$this->pendingWriteBuffer) {
            $this->pendingWriteBuffer .= array_shift($this->pendingWrites)->toRawData();
        }

        $this->log('Writing data to client, buffer contents: ' . $this->pendingWriteBuffer, Loggable::LEVEL_DEBUG);

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
        return strlen($this->pendingWriteBuffer) || count($this->pendingWrites);
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
     * Check whether this client performed the handshake
     *
     * @return boolean True when the client performed the handshake
     */
    public function didHandshake()
    {
        return $this->handshake->isComplete();
    }

    /**
     * Send a text message to the client
     *
     * @param string $data  The data to send
     * @param int    $flags A bitmask of flags
     */
    public function sendText($data, $flags = 0)
    {
        $this->log('Sending text message to client #' . $this->id);
        $this->log('Message data: ' . $data, Loggable::LEVEL_DEBUG);

        $fin = !($flags & self::SEND_PARTIAL);
        $rsv = $this->makeRSVField($flags);
        $opcode = Frame::OP_TEXT;

        $this->writeObject($this->messageEncoder->encodeString($data, $opcode, $fin, $rsv));
    }

    /**
     * Send a binary message to the client
     *
     * @param string $data  The data to send
     * @param int    $flags A bitmask of flags
     */
    public function sendBinary($data, $flags = 0)
    {
        $this->log('Sending text message to client #' . $this->id);
        $this->log('Message data: ' . $data, Loggable::LEVEL_DEBUG);

        $fin = !($flags & self::SEND_PARTIAL);
        $rsv = $this->makeRSVField($flags);
        $opcode = Frame::OP_BIN;

        $this->writeObject($this->messageEncoder->encodeString($data, $opcode, $fin, $rsv));
    }

    /**
     * Send a ping message to a specific client
     */
    public function ping()
    {
        // TODO: implement this properly
        $this->log('Sending ping frame to client #' . $this->id);
        $this->log('Message data:', Loggable::LEVEL_DEBUG);

        $this->writeObject($this->messageEncoder->encodeString('', Frame::OP_PING));
    }

    /**
     * Disconnect client
     *
     * @param string $message The message to be sent in the close frame
     */
    public function disconnect($closeMessage = '')
    {
        if ($this->isConnected()) {
            if (!feof($this->socket)) {
                if ($this->didHandshake()) {
                    $this->sendClose((string) $closeMessage);
                }

                fclose($this->socket);
            }

            $this->socket = null;

            $this->trigger('disconnect', $this);

            if ($this->server->getClientById($this->id)) {
                $this->server->removeClient($this);
            }
        }
    }

    /**
     * Set an application data property
     *
     * @param string $name  The name of the property
     * @param mixed  $value The value of the property
     */
    public function setAppData($name, $value) {
        $this->appData[$name] = $value;
    }

    /**
     * Get an application data property
     *
     * @param string $name  The name of the property
     *
     * @return mixed The value of the property
     */
    public function getAppData($name) {
        return isset($this->appData[$name]) ? $this->appData[$name] : null;
    }
}