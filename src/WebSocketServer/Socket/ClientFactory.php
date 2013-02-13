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
    \WebSocketServer\Http\RequestFactory,
    \WebSocketServer\Http\ResponseFactory,
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
     * @var \WebSocketServer\Log\Loggable The logger
     */
    private $logger;

    /**
     * Build the client factory object
     *
     * @param \WebSocketServer\Event\EventFactory   $eventFactory    Event factory
     * @param \WebSocketServer\Http\RequestFactory  $requestFactory  Factory which http request objects
     * @param \WebSocketServer\Http\ResponseFactory $responseFactory Factory which http response objects
     * @param \WebSocketServer\Socket\FrameFactory  $frameFactory    Frame factory
     * @param \WebSocketServer\Log\Loggable         $logger          The logger
     */
    public function __construct(
        EventFactory $eventFactory,
        RequestFactory $requestFactory,
        ResponseFactory $responseFactory,
        FrameFactory $frameFactory,
        Loggable $logger = null
    ) {
        $this->eventFactory    = $eventFactory;
        $this->requestFactory  = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->frameFactory    = $frameFactory;
        $this->logger          = $logger;
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
            $socket,
            $securityMethod,
            $server,
            $this->eventFactory,
            $this->requestFactory,
            $this->responseFactory,
            $this->frameFactory,
            $this->logger
        );
    }
}