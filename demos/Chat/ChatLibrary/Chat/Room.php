<?php
/**
 * This class represent a single chatroom
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

use ChatLibrary\Chat\User;

/**
 * This class represent a single chatroom
 *
 * @category   ChatLibrary
 * @package    Chat
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Room
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
     * @param \ChatLibrary\Chat\User $user The user to add
     */
    public function addUser(User $user)
    {
        var_dump('ADDING USER TO ROOM::' . $user->getId());
        if ($this->userIsInRoom($user->getId())) {
            echo 'EEEEK USER EXISTS!?!?!?!';
            return;
        }

        $user->registerRoom($this->id);

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

    public function getUsers()
    {
        return $this->users;
    }

    public function getUser($userId)
    {
        return $this->users[$userId];
    }
}