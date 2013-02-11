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
    \WebSocketServer\Event\Handler as EventHandler,
    \WebSocketServer\Log\Loggable,
    \WebSocketServer\Http\RequestFactory,
    \WebSocketServer\Http\ResponseFactory;

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
     * Build the client factory object
     *
     * @param \WebSocketServer\Event\Handler        $eventHandler    Event handler
     * @param \WebSocketServer\Log\Loggable         $logger          The logger
     * @param \WebSocketServer\Http\RequestFactory  $requestFactory  Factory which http request objects
     * @param \WebSocketServer\Http\ResponseFactory $responseFactory Factory which http response objects
     * @param \WebSocketServer\Socket\FrameFactory  $frameFactory    Frame factory
     */
    public function __construct(
        EventHandler $eventHandler,
        Loggable $logger,
        RequestFactory $requestFactory,
        ResponseFactory $responseFactory,
        FrameFactory $frameFactory
    ) {
        $this->eventHandler    = $eventHandler;
        $this->logger          = $logger;
        $this->requestFactory  = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->frameFactory    = $frameFactory;
    }

    /**
     * Build the instance of the socket client
     *
     * @param resource                       $socket The socket the client uses
     * @param \WebSocketServer\Core\Server   $id     The unique identifier for this client
     *
     * @return \WebSocketServer\Socket\Client New instance of a socket client
     */
    public function create($socket, Server $server)
    {
        return new Client(
            $socket,
            $server,
            $this->eventHandler,
            $this->logger,
            $this->requestFactory,
            $this->responseFactory,
            $this->frameFactory
        );
    }
}