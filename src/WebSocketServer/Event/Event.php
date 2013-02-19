<?php
/**
 * Class representing an event
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
 * Class representing an event
 *
 * @category   WebSocketServer
 * @package    Event
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class Event
{
    /**
     * @var \WebSocketServer\Event\EventEmitter The object that emitted the event
     */
    private $sourceObject;

    /**
     * @var string The name of the event
     */
    private $eventName;

    /**
     * @var array The arguments passed to the event handlers
     */
    private $arguments;

    /**
     * @var bool Whether the event will propagate
     */
    private $continuationStopped = false;

    /**
     * Build the event
     *
     * @param \WebSocketServer\Event\EventEmitter $sourceObject The object that emitted the event
     * @param string                              $eventName    The name of the event
     * @param array                               $arguments    The arguments passed to the event handlers
     */
    public function __construct($sourceObject, $eventName, array $arguments)
    {
        $this->sourceObject = $sourceObject;
        $this->eventName = $eventName;
        $this->arguments = $arguments;
    }

    /**
     * Get the object that emitted the event
     *
     * @return \WebSocketServer\Event\EventEmitter The object that emitted the event
     */
    public function getSourceObject()
    {
        return $this->sourceObject;
    }

    /**
     * Get the name of the event
     *
     * @return string The name of the event
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * Get the arguments passed to the event handlers
     *
     * @return array The arguments passed to the event handlers
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Stop continuation of the event through the handler queue
     */
    public function stopContinuation()
    {
        $this->continuationStopped = true;
    }

    /**
     * Start continuation of the event through the handler queue
     */
    public function startContinuation()
    {
        $this->continuationStopped = false;
    }

    /**
     * Determine whether continuation of the event through the handler queue has been stopped
     *
     * @return bool Whether continuation of the event through the handler queue has been stopped
     */
    public function isContinuationStopped()
    {
        return $this->continuationStopped;
    }
}