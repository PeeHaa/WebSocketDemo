<?php
/**
 * Interface for event emitting objects
 *
 * PHP version 5.4
 *
 * @category   WebSocketServer
 * @package    Event
 * @author     Chris Wright <https://github.com/DaveRandom>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace WebSocketServer\Event;

/**
 * Interface for event emitting objects
 *
 * @category   WebSocketServer
 * @package    Event
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
interface EventEmitter
{
    /**
     * Register an event handler callback
     *
     * @param string   $eventName The event name
     * @param callable $callback  The event handler
     */
    public function on($eventName, callable $callback);

    /**
     * Unregister a single event handler callback or all handlers for an event
     *
     * @param string   $eventName The event name
     * @param callable $callback  The event handler
     */
    public function off($eventName, callable $callback = null);

    /**
     * Trigger an event
     *
     * @param string $eventName The event name
     * @param mixed  $arg,...   Arguments passed to the event handler
     */
    public function trigger($eventName);
}