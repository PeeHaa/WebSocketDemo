<?php
/**
 * Factory for Event objects
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
 * Factory for Event objects
 *
 * @category   WebSocketServer
 * @package    Event
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class EventFactory
{
    /**
     * Create an event object
     *
     * @param object $sourceObject The object that emitted the event
     * @param string $eventName    The name of the event
     * @param array  $arguments    The arguments passed to the event handlers
     */
    public function create($sourceObject, $eventName, array $arguments)
    {
        return new Event($sourceObject, $eventName, $arguments);
    }
}