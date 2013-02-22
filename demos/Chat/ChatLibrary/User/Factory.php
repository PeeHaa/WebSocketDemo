<?php
/**
 * This factory builds user objects
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

use ChatLibrary\User\Entity;

/**
 * This factory builds user objects
 *
 * @category   ChatLibrary
 * @package    User
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 */
class Factory
{
    public function create($id)
    {
        return new Entity($id);
    }
}