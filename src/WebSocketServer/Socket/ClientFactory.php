<?php
/**
 * Factory class for Clients
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
    \WebSocketServer\Log\Loggable;

/**
 * Factory class for Clients
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class ClientFactory
{
    /**
     * @var \WebSocketServer\Socket\HandshakeFactory Handshake factory
     */
    private $handshakeFactory;

    /**
     * @var \WebSocketServer\Socket\DataBufferFactory Buffer factory
     */
    private $bufferFactory;

    /**
     * @var \WebSocketServer\Socket\MessageEncoderFactory Message encoder factory
     */
    private $messageEncoderFactory;

    /**
     * @var \WebSocketServer\Socket\MessageDecoderFactory Message decoder factory
     */
    private $messageDecoderFactory;

    /**
     * @var \WebSocketServer\Log\Loggable The logger
     */
    private $logger;

    /**
     * Build the client factory object
     *
     * @param \WebSocketServer\Socket\HandshakeFactory      $handshakeFactory      Handshake factory
     * @param \WebSocketServer\Socket\DataBufferFactory     $bufferFactory         Buffer factory
     * @param \WebSocketServer\Socket\MessageEncoderFactory $messageEncoderFactory Message encoder factory
     * @param \WebSocketServer\Socket\MessageDecoderFactory $messageDecoderFactory Message decoder factory
     * @param \WebSocketServer\Log\Loggable                 $logger                The logger
     */
    public function __construct(
        HandshakeFactory $handshakeFactory,
        DataBufferFactory $bufferFactory,
        MessageEncoderFactory $messageEncoderFactory,
        MessageDecoderFactory $messageDecoderFactory,
        Loggable $logger = null
    ) {
        $this->handshakeFactory      = $handshakeFactory;
        $this->bufferFactory         = $bufferFactory;
        $this->messageEncoderFactory = $messageEncoderFactory;
        $this->messageDecoderFactory = $messageDecoderFactory;
        $this->logger                = $logger;
    }

    /**
     * Build the instance of the socket client
     *
     * @param resource                     $socket         The socket the client uses
     * @param int                          $securityMethod The \STREAM_CRYPTO_METHOD_* constant used for enabling security
     * @param \WebSocketServer\Core\Server $server         The server instance the client belongs to
     *
     * @return \WebSocketServer\Socket\Client New instance of a socket client
     */
    public function create($socket, $securityMethod, Server $server)
    {
        return new Client(
            $socket, $securityMethod, $server,
            $this->handshakeFactory->create(),
            $this->bufferFactory->create(),
            $this->messageEncoderFactory->create(),
            $this->messageDecoderFactory->create(),
            $this->logger
        );
    }
}