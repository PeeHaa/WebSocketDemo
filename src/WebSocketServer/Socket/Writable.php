<?php
/**
 * Interface for objects that are directly writable to the socket
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Socket;

/**
 * Interface for objects that are directly writable to the socket
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
interface Writable
{
    /**
     * Get the raw data to write to the socket
     *
     * @return string The raw data
     */
    public function toRawData();
}