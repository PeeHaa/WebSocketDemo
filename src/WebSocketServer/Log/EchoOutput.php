<?php
/**
 * Simple logger which just echo messages prepended by [LOG]
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

use WebSocketServer\Log\Loggable;

/**
 * Simple logger which just echo messages prepended by [LOG]
 *
 * @category   WebSocketServer
 * @package    Log
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class EchoOutput implements Loggable
{
    /**
     * Write a message to the log
     *
     * @param string $message The message
     */
    public function write($message)
    {
        echo '[LOG] [' . (new \DateTime())->format('d-m-Y H:i:s') . '] '. $message . "\n";
    }
}