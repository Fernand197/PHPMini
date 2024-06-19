<?php

namespace App\Models;

use PHPMini\Models\Model;

/**
 * @property string $username
 * @property string $password
 * @property string $email
 * **/
class User extends Model
{
    protected static $table = 'users';
}