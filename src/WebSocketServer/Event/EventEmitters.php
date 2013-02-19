<?php
/**
 * Trait with default implementation for the EventEmitter interface
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
 * Trait with default implementation for the EventEmitter interface
 *
 * @category   WebSocketServer
 * @package    Event
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
trait EventEmitters
{
    /**
     * @var array[] Collection of registered event handlers
     */
    private $eventHandlers = [];

    /**
     * Register an event handler callback
     *
     * @param string   $eventName The event name
     * @param callable $callback  The event handler
     */
    public function on($eventName, callable $callback)
    {
        if (!isset($this->eventHandlers[$eventName])) {
            $this->eventHandlers[$eventName] = [];
        }

        $this->eventHandlers[$eventName][] = $callback;
    }

    /**
     * Unregister a single event handler callback or all handlers for an event
     *
     * @param string   $eventName The event name
     * @param callable $callback  The event handler
     */
    public function off($eventName, callable $callback = null)
    {
        if (isset($this->eventHandlers[$eventName])) {
            if (isset($callback)) {
                $key = array_search($callback, $this->eventHandlers[$eventName], true);
                if ($key !== false) {
                    array_splice($this->eventHandlers[$eventName], $key, 1);
                }
            } else {
                $this->eventHandlers[$eventName] = [];
            }
        }
    }

    /**
     * Trigger an event
     *
     * @param string $eventName The event name
     * @param mixed  $arg,...   Arguments passed to the event handler
     *
     * @return bool The success state returned by the event callbacks
     */
    public function trigger($eventName)
    {
        $result = true;

        if (isset($this->eventHandlers[$eventName])) {
            $args = func_get_args();
            array_shift($args);

            $event = new Event($this, $eventName, $args);
            array_unshift($args, $event);

            foreach ($this->eventHandlers[$eventName] as $handler) {
                $handlerResult = call_user_func_array($handler, $args);

                if ($handlerResult === false || $event->isContinuationStopped()) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }
}