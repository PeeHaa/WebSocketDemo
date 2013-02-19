<?php
/**
 * Data buffer class
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
 * Data buffer class
 *
 * @category   WebSocketServer
 * @package    Socket
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class DataBuffer implements Buffer
{
    /**
     * @var string The data in the buffer
     */
    private $buffer = '';

    /**
     * Return the buffer as a string
     */
    public function __toString() {
        return (string) $this->buffer;
    }

    /**
     * Append data to the buffer
     *
     * @param string $data The data to append
     */
    public function write($data)
    {
        $this->buffer .= $data;
    }

    /**
     * Return data from the buffer
     *
     * @param int $length Up to this number of bytes will be read
     * @param int $flags  A bitmask of READ_* constants
     *
     * @return string     The data
     */
    public function read($length, $offset = 0, $flags = 0)
    {
        $length = (int) $length;
        $offset = (int) $offset;
        $flags  = (int) $flags;

        if ($flags & self::READ_PEEK) {
            return substr($this->buffer, $offset, $length);
        }

        $data = substr($this->buffer, $offset, $length);
        if ($offset) {
            $this->buffer = substr($this->buffer, 0, $offset) . substr($this->buffer, $offset + $length);
        } else {
            $this->buffer = substr($this->buffer, $length);
        }

        return $data;
    }

    /**
     * Return a line of data from the buffer
     *
     * @param string $terminator The line terminator sequence
     * @param int    $flags      A bitmask of READ_* constants
     *
     * @return string            The line of data
     */
    public function readLine($terminator = "\n", $flags = 0)
    {
        if ($flags & self::NEEDLE_REGEX) {
            if (preg_match($terminator, $this->buffer, $matches, PREG_OFFSET_CAPTURE)) {
                $pos = $matches[0][1];
                $length = strlen($matches[0][0]);
            } else {
                $pos = false;
            }
        } else {
            $pos = strpos($this->buffer, $terminator);
            $length = strlen($terminator);
        }

        if ($pos === false) {
            return $flags & self::READ_PEEK ? $this->buffer : $this->drain();
        }

        return $this->read($pos + $length, 0, $flags);
    }

    /**
     * Return all data from the buffer and truncate
     *
     * @return string The data
     */
    public function drain()
    {
        $data = $this->buffer;
        $this->buffer = '';

        return $data;
    }

    /**
     * Destroy all data in the buffer
     */
    public function truncate()
    {
        $this->buffer = '';
    }

    /**
     * Search for a string in the buffer and return the offset of the first occurence
     *
     * @param string $needle The string to search for
     * @param int    $offset Position in the buffer to start the search
     * @param int    $flags  A bitmask of buffer constants
     *
     * @return int           The position of the search string or FALSE if it doesn't exist
     */
    public function indexOf($needle, $offset = 0, $flags = 0)
    {
        if ($flags & self::NEEDLE_REGEX) {
            if (preg_match($needle, $this->buffer, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                return $matches[0][1];
            }

            return false;
        }

        return strpos($this->buffer, $needle, $offset);
    }

    /**
     * Get the current length of the data in the buffer
     *
     * @return int The current length of the data in the buffer
     */
    public function length()
    {
        return strlen($this->buffer);
    }
}