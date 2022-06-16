<?php

namespace App\Exceptions\Container;

use PHPMini\Container\Exceptions\NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
}