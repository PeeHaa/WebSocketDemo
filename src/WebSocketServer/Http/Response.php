<?php
/**
 * This class represents an HTTP response. Meaning a request made from the server to the client.
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
 * This class represents an HTTP response. Meaning a request made from the server to the client.
 *
 * @category   WebSocketServer
 * @package    Http
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Response
{
    /**
     * @var array The headers of the response
     */
    private $headers = [];

    /**
     * @var array The body of the response
     */
    private $body;

    /**
     * Add a header to the response
     *
     * @param string      $key   The name of the header field
     * @param string|null $value The value of the header field
     */
    public function addHeader($key, $value = null)
    {
        $this->headers[$key] = $value;
    }

    /**
     * Add a body to the response
     *
     * @param string $content The body of the response
     */
    public function setBody($content)
    {
        $this->body = $content;
    }

    /**
     * Build the response to send back to the client
     *
     * @return string The response
     */
    public function buildResponse()
    {
        return $this->buildHeaders() . "\r\n";
    }

    /**
     * Build the headers for the response
     *
     * @return string The combined and concatenated header fields
     */
    private function buildHeaders()
    {
        $headers = '';
        foreach ($this->headers as $key => $value) {
            if ($value !== null) {
                $key .= ': ';
            }

            $headers .= $key . $value . "\r\n";
        }

        return $headers;
    }
}