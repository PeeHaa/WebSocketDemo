<?php
/**
 * This class represents a websocket request. Meaning a request made from the client to the server.
 * Basically it is a simplified API which parses the request headers
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Http
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Http;

use WebSocketServer\Http\Parser\Parsable;

/**
 * This class represents a websocket request. Meaning a request made from the client to the server.
 *
 * @category   WebSocketServer
 * @package    Http
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Request
{
    /**
     * @var \WebSocketServer\Http\Parser\Parsable An HTTP request parser
     */
    private $parser;

    /**
     * @var string The resource
     */
    private $resource;

    /**
     * @var string The host header
     */
    private $host;

    /**
     * @var string The origin header
     */
    private $origin;

    /**
     * @var string The key header
     */
    private $key;

    /**
     * Build the request object
     *
     * @param \WebSocketServer\Http\Parser\Parsable $parser An HTTP request parser
     */
    public function __construct(Parsable $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Parse the request and fill the properties
     */
    public function parse()
    {
        $this->parser->parse();

        $this->resource = $this->parser->getResource();

        $headers = $this->parser->getHeaders();

        $requiredHeaders = ['host', 'origin', 'sec-websocket-key'];
        foreach ($requiredHeaders as $requiredHeader) {
            if (!array_key_exists($requiredHeader, $headers)) {
                throw new \RangeException('Invalid request. Missing required header (`' . $requiredHeader . '`).');
            }
        }

        $this->host   = $headers['host'];
        $this->origin = $headers['origin'];
        $this->key    = $headers['sec-websocket-key'];
    }

    /**
     * Get the resource of the request
     *
     * @return string The resource of the request
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Get the host header of the request
     *
     * @return string The host header of the request
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get the origin header of the request
     *
     * @return string The origin header of the request
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Get the key header of the request
     *
     * @return string The key header of the request
     */
    public function getKey()
    {
        return $this->key;
    }
}