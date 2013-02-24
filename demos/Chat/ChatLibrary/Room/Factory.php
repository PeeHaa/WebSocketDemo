<?php
/**
 * This class builds new room instances
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

use ChatLibrary\Room\Entity;

/**
 * This class builds new room instances
 *
 * @category   ChatLibrary
 * @package    Room
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Factory
{
    /**
     * Build a new instance of a chatroom
     *
     * @param int    $id   The id of the new room
     * @param string $name The name of the new room
     *
     * @return \ChatLibrary\Room\Entity The new room instance
     */
    public function create($id, $name)
    {
        return new Entity($id, $name);
    }
}