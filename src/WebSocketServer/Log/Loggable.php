<?php
/**
 * Interface for a logger
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Log
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Log;

/**
 * Interface for a logger
 *
 * @category   WebSocketServer
 * @package    Log
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
interface Loggable
{
    /**
     * Write a message to the log
     *
     * @param string $message The message
     */
    public function write($message);
}