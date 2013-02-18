<?php
/**
 * This class represents a websocket client handshake
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

use \WebSocketServer\Http\Request,
    \WebSocketServer\Http\Response;

/**
 * This class represents a websocket client handshake
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class Handshake
{
    const SIGNING_KEY = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'; // TODO: allow this value to be injected?

    /**
     * @var \WebSocketServer\Http\Request HTTP request object
     */
    private $request;

    /**
     * @var \WebSocketServer\Http\Response HTTP response object
     */
    private $response;

    /**
     * @var bool Whether the handshake is complete
     */
    private $complete;

    /**
     * Build the handshake object
     *
     * @param \WebSocketServer\Http\Request  $request  HTTP request object
     * @param \WebSocketServer\Http\Response $response HTTP response object
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request  = $request;
        $this->response = $response;

        $response->setResponseLine('HTTP/1.1 101 WebSocket Protocol Handshake');
        $response->addHeader('Upgrade', 'WebSocket');
        $response->addHeader('Connection', 'Upgrade');
    }

    /**
     * Generate a signature to be used when shaking hands with the client
     *
     * @param string $key The key used to sign the response
     *
     * @return string The signture
     */
    private function buildSignature($key)
    {
        return base64_encode(sha1($key . self::SIGNING_KEY, true));
    }

    /**
     * Build the instance of the handshake
     *
     * @return bool Whether a complete handshake request has been received
     */
    public function readClientHandshake(Buffer $buffer)
    {
        if (!$buffer->indexOf('/\r?\n\r?\n/', 0, Buffer::NEEDLE_REGEX) !== false) {
            return false;
        }

        $headers = $buffer->readLine('/\r?\n\r?\n/', Buffer::NEEDLE_REGEX);

        $this->request->parseString($headers);

        if ($this->request->getMethod() !== 'GET') {
            throw new \RangeException('Invalid request - method must be GET');
        }
        if ($this->request->getHttpVersion() < 1.1) {
            throw new \RangeException('Invalid request - request protocol version must be >=1.1');
        }

        $length = $this->request->getHeader('content-length');
        if ($length && current($length)) {
            throw new \RangeException('Invalid request - GET requests cannot contain a non-empty entity-body');
        }
        if (!$this->request->getHeader('host')) {
            throw new \RangeException('Invalid request - missing Host: header');
        }
        if (!$key = $this->request->getHeader('sec-websocket-key')) {
            throw new \RangeException('Invalid request - missing Sec-Websocket-Key: header');
        }

        $key = current($key);
        $this->response->addHeader('Sec-WebSocket-Accept', $this->buildSignature($key));

        return true;
    }

    /**
     * Check whether the handshake is complete
     *
     * @return bool Whether the handshake is complete
     */
    public function isComplete()
    {
        return $this->complete;
    }

    /**
     * Gets the internal request object
     *
     * @return \WebSocketServer\Http\Request The internal request object
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Gets the internal response object
     *
     * @return \WebSocketServer\Http\Response The internal response object
     */
    public function getResponse()
    {
        return $this->response;
    }
}