<?php
/**
 * This class represent a single chatroom
 *
 * PHP version 5.4
 *
 * @category   ChatLibrary
 * @package    Room
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace ChatLibrary\Room;

use ChatLibrary\User\Entity as UserEntity;

/**
 * This class represent a single chatroom
 *
 * @category   ChatLibrary
 * @package    Room
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Entity
{
    /**
     * @var int The unique id of the room
     */
    private $id;

    /**
     * @var string The name of the chatroom
     */
    private $name;

    /**
     * @var boolean Whether the room will be listed
     */
    private $public = true;

    /**
     * @var null|string The (optional) password to enter the room
     */
    private $password = null;

    /**
     * @var array List of connected users
     */
    private $users = [];

    /**
     * @var array List of messages posted in the room
     */
    private $messages = [];

    /**
     * Build the room object
     *
     * @param int         $id       The unique id of the room
     * @param string      $name     The name of the room
     * @param boolean     $public   Whether the room will be listed
     * @param null|string $password The (optional) password to enter the room
     */
    public function __construct($id, $name, $public = true, $password = null)
    {
        $this->id       = $id;
        $this->name     = $name;
        $this->public   = $public;
        $this->password = $password;
    }

    /**
     * Get the unique id of the room
     *
     * @return int The unique id of the room
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the name of the room
     *
     * @return int The name of the room
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add a user to the room
     *
     * @param \ChatLibrary\User\Entity $user The user to add
     */
    public function addUser(UserEntity $user)
    {
        if ($this->userIsInRoom($user->getId())) {
            return;
        }

        //$user->registerRoom($this->id);

        $this->users[$user->getId()] = $user;
    }

    /**
     * Remove a user from the room
     *
     * @param int $userId The id of the user to remove
     */
    public function removeUserById($userId)
    {
        if (!$this->userIsInRoom($userId)) {
            return;
        }

        $this->users[$userId]->unregisterRoom($this->id);

        unset($this->users[$userId]);
    }

    /**
     * Check whether a user is currently in the room
     *
     * @param int $userId The id of the user
     *
     * @return boolean True when the user is currently in the room
     */
    private function userIsInRoom($userId)
    {
        return array_key_exists($userId, $this->users);
    }

    /**
     * Get all the users in the room
     *
     * @return array The users currently in the room
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Get all the parsed message posted in the room
     *
     * @return array The the parsed message posted in the room
     */
    public function getParsedMessages()
    {
        $messages = [];
        foreach ($this->messages as $message) {
            $messaged[] = $message->getInfo();
        }
    }

    public function getUser($userId)
    {
        return $this->users[$userId];
    }
}