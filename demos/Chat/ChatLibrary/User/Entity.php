<?php
/**
 * This class represents a user of the system.
 * A user is a "session" on the website. So it can make use of multiple streams.
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

use WebSocketServer\Socket\Client;

/**
 * This class represents a user of the system.
 *
 * @category   ChatLibrary
 * @package    User
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Entity
{
    const STATUS_ONLINE = 'online';
    const STATUS_TYPING = 'typing';
    const STATUS_IDLE   = 'idle';

    /**
     * @var array List of streams of the user
     */
    private $clients = [];

    /**
     * @var string The unique identifier of the user
     */
    private $id;

    /**
     * @var string The hashed unique identifier of the user. Useful when the id has to be shared with untrusted clients
     */
    private $hashedId;

    /**
     * @var string The username of the user.
     */
    private $username;

    /**
     * @var \DateTime The timestamp of the last activity by the user
     */
    private $lastSeen;

    /**
     * @var string The current status ofthe user
     */
    private $status = self::STATUS_IDLE;

    /**
     * Create instance
     *
     * @param string The unique identifier of the user
     *
     * @throws \DomainException When the hashing of the unique identifier failed
     */
    public function __construct($id)
    {
        $this->id       = $id;
        $this->hashedId = password_hash((string) $id, PASSWORD_DEFAULT);
        $this->lastSeen = new \DateTime();
        $this->status   = self::STATUS_ONLINE;

        if ($this->hashedId === false) {
            throw new \DomainException('Failed to hash the user id.');
        }
    }

    /**
     * Get the unique identifier of the user
     *
     * @return string The unique identifier of the user
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the hashed unique identifier of the user
     *
     * @return string The hashed unique identifier of the user
     */
    public function getHashedId()
    {
        return $this->hashedId;
    }

    /**
     * Set the username of the user
     *
     * @param string $username The username of the user
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Get the username of the user
     *
     * @return string The username of the user
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set the status of the user
     *
     * @param string The status of the user
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get the status of the user
     *
     * @return string The status of the user
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get the info of the user to be shared with untrusted clients. Not sure whether I really want a method
     * to return an array though
     *
     * @return array The user info to which can be shared with untrusted clients
     */
    public function getInfo()
    {
        return [
            'id'         => $this->getHashedId(),
            'username'   => $this->getUsername(),
            'avatarHash' => md5($this->getUsername()),
            'status'     => $this->getStatus(),
        ];
    }

    /**
     * Check whether the supplied hash matches the user's hashed unique identifier
     *
     * @param string The hash to match against
     *
     * @return boolean Whether the hash matches the user's hashed unique indentifier
     */
    public function matchesHashedId($hashedId)
    {
        return password_verify($this->getId(), $hashedId);
    }

    /**
     * Add a client (stream) to the user
     *
     * @param \WebSocketServer\Socket\Client $client The new client to add to the user
     */
    public function addClient(Client $client)
    {
        $this->clients[$client->getId()] = $client;
    }

    /**
     * Remove a client (stream) of the user
     *
     * @param \WebSocketServer\Socket\Client $client The new client to remove of the user
     */
    public function removeClient(Client $client)
    {
        unset($this->clients[$client->getId()]);
    }

    /**
     * Get the number of clients the user is connected to
     *
     * @return int The number of clients the user is connected to
     */
    public function numClients()
    {
        return count($this->clients);
    }
}