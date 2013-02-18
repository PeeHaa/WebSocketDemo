<?php
/**
 * Interface for data buffers
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
 * Interface for data buffers
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
interface Buffer
{
    /**
     * @const int Read data but do not consume the buffer
     */
    const READ_PEEK    = 0x01;

    /**
     * @const int Signifies that a needle should be evaluated as a regular expression
     */
    const NEEDLE_REGEX = 0x10;

    /**
     * Return the buffer as a string
     */
    public function __toString();

    /**
     * Append data to the buffer
     *
     * @param string $data The data to append
     */
    public function write($data);

    /**
     * Return data from the buffer
     *
     * @param int $length Up to this number of bytes will be read
     * @param int $flags  A bitmask of buffer constants
     *
     * @return string     The data
     */
    public function read($length, $offset = 0, $flags = 0);

    /**
     * Return a line of data from the buffer
     *
     * @param string $terminator The line terminator sequence
     * @param int    $flags      A bitmask of buffer constants
     *
     * @return string            The line of data
     */
    public function readLine($terminator = "\n", $flags = 0);

    /**
     * Return all data from the buffer and truncate
     *
     * @return string The data
     */
    public function drain();

    /**
     * Destroy all data in the buffer
     */
    public function truncate();

    /**
     * Search for a string in the buffer and return the offset of the first occurence
     *
     * @param string $needle The string to search for
     * @param int    $offset Position in the buffer to start the search
     * @param int    $flags  A bitmask of buffer constants
     *
     * @return int           The position of the search string or FALSE if it doesn't exist
     */
    public function indexOf($needle, $offset = 0, $flags = 0);

    /**
     * Get the current length of the data in the buffer
     *
     * @return int The current length of the data in the buffer
     */
    public function length();
}