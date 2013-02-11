<?php
/**
 * This class represents a websocket request. Meaning a request made from the client to the server.
 * Basically it is a simplified API which parses the request headers
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
 * This class represents a websocket request. Meaning a request made from the client to the server.
 *
 * @category   WebSocketServer
 * @package    Http
 * @subpackage Parser
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Request implements Parsable
{
    const HEADER_DELIMITER = "#\r?\n#";
    const REQUEST_DELIMITER = "#\r?\n\r?\n#";

    /**
     * @var string The raw data of the request
     */
    private $requestData;

    /**
     * @var string The raw data of the headers
     */
    private $headerData;

    /**
     * @var string The body of the request
     */
    private $body;

    /**
     * Build the parser object
     *
     * @param string $requestData The raw data of the request to parse
     */
    public function __construct($requestData)
    {
        $this->requestData = $requestData;
    }

    /**
     * Parse (/ split) the raw request data
     */
    public function parse()
    {
        $requestParts = preg_split(self::REQUEST_DELIMITER, $this->requestData, 2, PREG_SPLIT_NO_EMPTY);

        $this->headerData = $requestParts[0];
        $this->body = '';

        if (count($requestParts) > 1) {
            $this->body = $requestParts[1];
        }
    }

    /**
     * Get the resource from the header of the request. An example is: GET /WebSocketServer.php HTTP/1.1
     *
     * @return string The full HTTP resource
     * @throws \RangeException When the resource is missing in the request
     */
    public function getResource()
    {
        if (preg_match('/GET (.*) HTTP/i', $this->headerData, $match)) {
            return $match[1];
        }

        throw new \RangeException('Missing resource in HTTP header.');
    }

    /**
     * Get the HTTP method from the header of the request
     *
     * @return string The HTTP method
     * @throws \RangeException When the method is missing or is invalid
     */
    public function getMethod()
    {
        if (preg_match('/(GET|HEAD|POST|PUT|DELETE|TRACE|CONNECT)/i', $this->headerData, $match)) {
            return $match[1];
        }

        throw new \RangeException('Invalid method used in the request.');
    }

    /**
     * Get the headers of the request
     *
     * @return array The headers of the request
     */
    public function getHeaders()
    {
        $rawHeaders = preg_split(self::HEADER_DELIMITER, $this->headerData);

        $parsedHeaders = [];
        foreach ($rawHeaders as $rawHeader) {
            $headerParts = explode(': ', $rawHeader, 2);

            if (count($headerParts) < 2) {
                continue;
            }

            $parsedHeaders[strtolower($headerParts[0])] = $headerParts[1];
        }

        return $parsedHeaders;
    }

    /**
     * Get the body of the request
     *
     * @return string The body of the request
     */
    public function getBody()
    {
        return $this->body;
    }
}