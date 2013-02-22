<?php
/**
 * This class represent all chatrooms currently active
 *
 * PHP version 5.4
 *
 * @category   ChatLibrary
 * @package    Chat
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace ChatLibrary\Chat;

use ChatLibrary\Chat\Room;

/**
 * This class represent all chatrooms currently active
 *
 * @category   ChatLibrary
 * @package    Chat
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class RoomCollection
{
    /**
     * @var array The collection of the active rooms
     */
    private $rooms = [];

    /**
     * Add an active room
     *
     * @param \ChatLibrary\Chat\Room $room The room to add
     */
    public function add(Room $room)
    {
        if ($this->exists($room->getId())) {
            return;
        }

        $this->rooms[$room->getId()] = $room;
    }

    /**
     * Remove a room
     *
     * @param int $roomId The room to remove
     */
    public function remove($roomId)
    {
        if (!$this->exists($roomId)) {
            return;
        }

        $this->rooms[$roomId]->remove();

        unset($this->rooms[$roomId]);
    }

    /**
     * Check whether a room is active
     *
     * @param int $roomId The room to check
     *
     * @return boolean Whether the room is active
     */
    public function exists($roomId)
    {
        return array_key_exists($roomId, $this->rooms);
    }

    /**
     * Get all active rooms
     *
     * @return array All active rooms
     */
    public function getAll()
    {
        return $this->rooms;
    }

    /**
     * Get a list of the names of all rooms
     *
     * @return array List of the names of all rooms
     */
    public function getAllNames()
    {
        $rooms = [];
        foreach ($this->rooms as $room) {
            $rooms[$room->getId()] = $room->getName();
        }

        return $rooms;
    }

    /**
     * Get a specific room by id
     *
     * @param int $roomId The room to get
     *
     * @return null|\ChatLibrary\Chat\Room The room
     */
    public function getById($roomId)
    {
        if (!$this->exists($roomId)) {
            return null;
        }

        return $this->rooms[$roomId];
    }
}