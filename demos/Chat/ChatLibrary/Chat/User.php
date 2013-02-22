<?php
/**
 * This class represent a user
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

/**
 * This class represent a user
 *
 * @category   ChatLibrary
 * @package    Chat
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class User
{
    /**
     * @var int The unique id of the user
     */
    private $id;

    /**
     * @var string The username of the user
     */
    private $username;

    /**
     * @var array The rooms the user is currently in
     */
    private $rooms = [];

    /**
     * Build the user object
     *
     * @param int The unique id of the user
     * @param null|string The optional username
     */
    public function __construct($id, $username = null)
    {
        $this->id       = $id;
        $this->username = $username;

        if ($this->username === null) {
            $this->username = 'Anon ' . $this->id;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Register a new chatroom for the user
     *
     * @param int $roomId The room id to register
     */
    public function registerRoom($roomId)
    {
        if ($this->userIsInRoom($roomId)) {
            return;
        }

        $this->rooms[] = $roomId;
    }

    /**
     * Unregister a chatroom for the user
     *
     * @param int $roomId The room id to unregister
     */
    public function unregisterRoom($roomId)
    {
        if (!$this->userIsInRoom($roomId)) {
            return;
        }

        unset($this->rooms[array_search($roomId, $this->rooms)]);
    }

    /**
     * Check whether a user is currently in the room
     *
     * @param int $roomId The id of the room
     *
     * @return boolean True when the user is currently in the room
     */
    private function userIsInRoom($roomId)
    {
        return array_search($roomId, $this->rooms) !== false;
    }
}