<?php
/**
 * This factory builds HTTP request data objects
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

use WebSocketServer\Http\Parser\Request as RequestParser,
    WebSocketServer\Http\Request;

/**
 * This factory builds HTTP request data objects
 *
 * @category   WebSocketServer
 * @package    Http
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class RequestFactory
{
    /**
     * Build a request
     *
     * @param string $requestData The request data
     *
     * @return WebSocketServer\Http\Request The request object
     */
    public function create($requestData)
    {
        return new Request(new RequestParser($requestData));
    }
}