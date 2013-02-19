<?php
/**
 * This class represents a websocket frame
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Socket;

use \WebSocketServer\Http\RequestFactory,
    \WebSocketServer\Http\ResponseFactory;

/**
 * This class represents a websocket frame
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class HandshakeFactory
{
    /**
     * @var \WebSocketServer\Http\RequestFactory Factory which builds http request objects
     */
    private $requestFactory;

    /**
     * @var \WebSocketServer\Http\ResponseFactory Factory which builds http response objects
     */
    private $responseFactory;

    /**
     * Build the client factory object
     *
     * @param \WebSocketServer\Http\RequestFactory  $requestFactory  Factory which http request objects
     * @param \WebSocketServer\Http\ResponseFactory $responseFactory Factory which http response objects
     */
    public function __construct(RequestFactory $requestFactory, ResponseFactory $responseFactory)
    {
        $this->requestFactory  = $requestFactory;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Build the instance of the handshake
     *
     * @return \WebSocketServer\Socket\Handshake New instance of a socket client
     */
    public function create()
    {
        return new Handshake(
            $this->requestFactory->create(),
            $this->responseFactory->create()
        );
    }
}