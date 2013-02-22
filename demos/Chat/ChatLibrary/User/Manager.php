<?php
/**
 * This class manages all users of the system.
 *
 * PHP version 5.4
 *
 * @category   ChatLibrary
 * @package    User
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @copyright  Copyright (c) 2013 Pieter Hordijk
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
namespace ChatLibrary\User;

use ChatLibrary\User\Factory,
    ChatLibrary\User\Entity;

/**
 * This class manages all users of the system.
 *
 * @category   ChatLibrary
 * @package    User
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Manager
{
    /**
     * @var array List of currently connected users
     */
    private $users = [];

    /**
     * @var \ChatLibrary\User\Factory User factory
     */
    private $userFactory;

    /**
     * Create instance
     *
     * @param \ChatLibrary\User\Factory User factory
     */
    public function __construct(Factory $userFactory)
    {
        $this->userFactory = $userFactory;
    }

    /**
     * Get a user by its id
     *
     * @param string $id The user id
     *
     * @return \ChatLibrary\User\Entity The user
     */
    public function getUserById($id)
    {
        if (!isset($this->users[$id])) {
            $this->users[$id] = $this->userFactory->create($id);
        }

        return $this->users[$id];
    }

    /**
     * Get a user by its hashed id
     *
     * @param string $hashedId The user hashed id
     *
     * @return \ChatLibrary\User\Entity  The user
     * @throws \UnexpectedValueException When there is no user connected which matched with the hashed id
     */
    public function getUserByHashesId($hashedId)
    {
        foreach ($this->users as $user) {
            if (!$user->matchesHashedId($hashedId)) {
                continue;
            }

            return $user;
        }

        throw new \UnexpectedValueException('No user connected with hashed id: ' . $hashedId);
    }

    /**
     * Remove a user from the connected list
     *
     * \ChatLibrary\User\Entity The user to remove from the list
     */
    public function removeUser(Entity $user)
    {
        //unset($this->users[$user->getId()]);
    }
}