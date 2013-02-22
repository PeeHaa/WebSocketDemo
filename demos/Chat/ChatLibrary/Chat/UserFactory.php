<?php

namespace ChatLibrary\Chat;

use ChatLibrary\Chat\User;

class UserFactory
{
    public function build($id, $username = null)
    {
        return new User($id, $username);
    }
}