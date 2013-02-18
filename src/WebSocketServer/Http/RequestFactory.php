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
     * @return WebSocketServer\Http\Request The request object
     */
    public function create()
    {
        return new Request;
    }
}