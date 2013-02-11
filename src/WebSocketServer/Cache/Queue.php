<?php
/**
 * Simple poor man's cache implementation. Represents a queue of cached items.
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Cache
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Cache;

/**
 * Simple poor man's cache implementation. Represents a queue of cached items.
 *
 * @category   WebSocketServer
 * @package    Cache
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Queue
{
    /**
     * @var int The maximum size of the queue
     */
    private $size;

    /**
     * @var array The cache queue
     */
    private $queue = [];

    /**
     * Build the cache queue object
     *
     * @param int $size The maximum size of the cache queue
     */
    public function __construct($size = 1000)
    {
        $this->size = (int) $size;
    }

    /**
     * Check whether the element exists in cache
     *
     * @param mixed $key The key of the element to check
     *
     * @return boolean Whether the element is a valid cache resource
     */
    public function keyExists($key)
    {
        return array_key_exists($key, $this->queue);
    }

    /**
     * Get the cached value of an element
     *
     * @param mixed $key The key of the element to get
     *
     * @return mixed The ached value
     * @throws \RangeException If the requested element is an invalid cache object
     */
    public function getItem($key)
    {
        if ($this->keyExists($key) === false) {
            throw new \RangeException('Invalid key supplied (`' . $key . '`).');
        }

        $this->moveItemToTop($key);

        return $this->queue[$key];
    }

    /**
     * Add an element to the cache
     *
     * @param mixed $key   The key of the element to cache
     * @param mixed $value The value of the element to cache
     */
    public function add($key, $value)
    {
        if (count($this->queue) === $this->size) {
            array_shift($this->queue);
        }

        $this->queue[$key] = $value;
    }

    /**
     * Move an item to the top of the queue
     *
     * @param mixed $key   The key of the element to cache
     */
    private function moveItemToTop($key)
    {
        $value = $this->queue[$key];

        unset($this->queue[$key]);

        $this->queue[$key] = $value;
    }
}