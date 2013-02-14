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
class Handshake
{
    const SIGNING_KEY = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'; // TODO: allow this value to be injected

    /**
     * @var \WebSocketServer\Http\RequestFactory Factory which builds http request objects
     */
    private $requestFactory;

    /**
     * @var \WebSocketServer\Http\ResponseFactory Factory which builds http response objects
     */
    private $responseFactory;

    /**
     * @var \WebSocketServer\Http\ResponseFactory Factory which builds http response objects
     */
    private $readBuffer = '';

    /**
     * @var bool Whether the handshake is complete
     */
    private $complete;

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
     * Build the instance of the handshake
     *
     * @return \WebSocketServer\Socket\Handshake New instance of a socket client
     */
    public function doHandshake($buffer, $securityMethod)
    {
        $this->readBuffer .= $buffer;

        if (preg_match('/\r?\n\r?\n/', $this->readBuffer)) {
            $request  = $this->requestFactory->create($this->readBuffer);
            $request->parse();

            $protocol = $securityMethod ? 'wss' : 'ws';

            $response = $this->responseFactory->create();
            $response->addHeader('HTTP/1.1 101 WebSocket Protocol Handshake');
            $response->addHeader('Upgrade', 'WebSocket');
            $response->addHeader('Connection', 'Upgrade');
            $response->addHeader('Sec-WebSocket-Origin', $request->getOrigin());
            $response->addHeader('Sec-WebSocket-Location', $protocol . '://' . $request->getHost() . $request->getResource());
            $response->addHeader('Sec-WebSocket-Accept', $this->getSignature($request->getKey()));

            $this->complete = true;

            return $response->buildResponse();
        }

        return false;
    }

    /**
     * @return bool Whether the handshake is complete
     */
    public function isComplete()
    {
        return $this->complete;
    }
}