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
     * @var string The method of the request
     */
    private $method;

    /**
     * @var string The URI of the request
     */
    private $uri;

    /**
     * @var string The HTTP version of the request
     */
    private $httpVersion;

    /**
     * @var array Request headers
     */
    private $headers = [];

    /**
     * @var array Request cookies
     */
    private $cookies = [];

    /**
     * @var array Request URL parameters
     */
    private $urlParams = [];

    /**
     * Parse the request and fill the properties
     *
     * @param string $headers The headers of the handshake HTTP request
     */
    public function parseString($headers)
    {
        $expr = '%
            ^
            ([A-Z]+)\s+(\S+)\s+HTTP/(\d+\.\d+)\r?\n
            ((?:.+?\r?\n)*?)
            \r?\n
            $
        %ix';
        if (!preg_match($expr, $headers, $matches)) {
            throw new \InvalidArgumentException('Input is not a valid HTTP request');
        }

        $this->method = strtoupper($matches[1]);
        $this->httpVersion = $matches[3];

        $parts = explode('?', $matches[2], 2);
        $this->uri = $parts[0];
        if (isset($parts[1])) {
            parse_str($parts[1], $this->urlParams);
        }

        $expr = '%([a-z\-]+)(?::(.*?)(?:(?:\r?\n(?![ \t]))|$))?%is';
        $headers = trim($matches[4]);

        if (preg_match_all($expr, $headers, $matches)) {
            foreach ($matches[1] as $i => $name) {
                $name = strtolower($name);
                $value = preg_replace('/\r?\n\s+/', ' ', trim($matches[2][$i]));

                if (!isset($this->headers[$name])) {
                    $this->headers[$name] = [];
                }
                $this->headers[$name][] = $value;
            }

            if (isset($this->headers['cookie'])) {
                foreach ($this->headers['cookie'] as $cookies) {
                    if (preg_match_all('/(?:;\s*)?([^=]+)=([^;]+)/', $cookies, $matches)) {
                        foreach ($matches[1] as $i => $name) {
                            $this->cookies[$name] = $matches[2][$i];
                        }
                    }
                }
            }
        }
    }

    /**
     * Get the method of the request
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * Get the URI of the request
     */
    public function getUri() {
        return $this->uri;
    }

    /**
     * Get the HTTP version of the request
     */
    public function getHttpVersion() {
        return $this->httpVersion;
    }

    /**
     * Get a header by name
     *
     * @param string $name The name of the header
     */
    public function getHeader($name) {
        $name = strtolower($name);
        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    /**
     * Get a cookie by name
     *
     * @param string $name The name of the cookie
     */
    public function getCookie($name) {
        return isset($this->cookies[$name]) ? $this->cookies[$name] : null;
    }

    /**
     * Get a url parameter by name
     *
     * @param string $name The name of the url parameter
     */
    public function getUrlVar($name) {
        return isset($this->urlParams[$name]) ? $this->urlParams[$name] : null;
    }
}