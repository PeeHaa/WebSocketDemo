<?php
/**
 * This factory builds client sockets
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
    \WebSocketServer\Event\EventFactory,
    \WebSocketServer\Log\Loggable;

/**
 * This factory builds client sockets
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class ClientFactory
{
    /**
     * @var \WebSocketServer\Event\EventFactory Event factory
     */
    private $eventFactory;

    /**
     * @var \WebSocketServer\Socket\HandshakeFactory Handshake factory
     */
    private $handshakeFactory;

    /**
     * @var \WebSocketServer\Socket\DataBufferFactory Buffer factory
     */
    private $bufferFactory;

    /**
     * @var \WebSocketServer\Socket\FrameFactory Frame factory
     */
    private $frameFactory;

    /**
     * @var \WebSocketServer\Log\Loggable The logger
     */
    private $logger;

    /**
     * Build the client factory object
     *
     * @param \WebSocketServer\Event\EventFactory       $eventFactory     Event factory
     * @param \WebSocketServer\Socket\HandshakeFactory  $handshakeFactory Handshake factory
     * @param \WebSocketServer\Socket\DataBufferFactory $bufferFactory    Buffer factory
     * @param \WebSocketServer\Socket\FrameFactory      $frameFactory     Frame factory
     * @param \WebSocketServer\Log\Loggable             $logger           The logger
     */
    public function __construct(
        EventFactory $eventFactory,
        HandshakeFactory $handshakeFactory,
        DataBufferFactory $bufferFactory,
        FrameFactory $frameFactory,
        Loggable $logger = null
    ) {
        $this->eventFactory     = $eventFactory;
        $this->handshakeFactory = $handshakeFactory;
        $this->bufferFactory    = $bufferFactory;
        $this->frameFactory     = $frameFactory;
        $this->logger           = $logger;
    }

    /**
     * Build the instance of the socket client
     *
     * @param resource                     $socket         The socket the client uses
     * @param int                          $securityMethod The \STREAM_CRYPTO_METHOD_* constant used for enabling security
     * @param \WebSocketServer\Core\Server $id             The unique identifier for this client
     *
     * @return \WebSocketServer\Socket\Client New instance of a socket client
     */
    public function create($socket, $securityMethod, Server $server)
    {
        return new Client(
            $socket, $securityMethod, $server,
            $this->eventFactory,
            $this->handshakeFactory->create(),
            $this->bufferFactory->create(),
            $this->frameFactory,
            $this->logger
        );
    }
}