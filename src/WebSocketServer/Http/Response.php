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
    private $responseLine;

    /**
     * @var array The headers of the response
     */
    private $headers = [];

    /**
     * Add a header to the response
     *
     * @param string $name  The name of the header field
     * @param string $value The value of the header field
     */
    public function addHeader($name, $value)
    {
        $name = strtolower($name);

        if (!isset($this->headers[$name])) {
            $this->headers[$name] = [];
        }

        $this->headers[$name][] = $value;
    }

    /**
     * Set the response line
     *
     * @param string $responseLine The response line
     */
    public function setResponseLine($responseLine)
    {
        $this->responseLine = trim($responseLine);
    }

    /**
     * Build the response to send back to the client
     *
     * @return string The response
     */
    public function buildResponse()
    {
        return $this->responseLine . "\r\n" . $this->buildHeaders() . "\r\n";
    }

    /**
     * Build the headers for the response
     *
     * @return string The combined and concatenated header fields
     */
    private function buildHeaders()
    {
        $headers = [];
        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                $name = preg_replace('/(?<=^|-)[a-z]/', function($matches) {
                    return strtoupper($matches[0]);
                }, $name);
                $headers[] = $name . ': ' . $value;
            }
        }

        return $headers ? implode("\r\n", $headers) . "\r\n" : '';
    }
}