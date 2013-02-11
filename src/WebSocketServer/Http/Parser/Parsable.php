<?php
/**
 * Interface for HTTP request parsers
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Http
 * @subpackage Parser
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Http\Parser;

/**
 * Interface for HTTP request parsers
 *
 * @category   WebSocketServer
 * @package    Http
 * @subpackage Parser
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
interface Parsable
{
    /**
     * Parse (/ split) the raw request data
     */
    public function parse();

    /**
     * Get the resource from the header of the request. An example is: GET /WebSocketServer.php HTTP/1.1
     *
     * @return string The full HTTP resource
     * @throws \RangeException When the resource is missing in the request
     */
    public function getResource();

    /**
     * Get the HTTP method from the header of the request
     *
     * @return string The HTTP method
     * @throws \RangeException When the method is missing or is invalid
     */
    public function getMethod();

    /**
     * Get the headers of the request
     *
     * @return array The headers of the request
     */
    public function getHeaders();

    /**
     * Get the body of the request
     *
     * @return string The body of the request
     */
    public function getBody();
}