<?php

namespace Tests\Unit;

use PHPMini\Container\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function test_container_resolve()
    {
        $container = new Container();
        
        $c = $container->get(Container::class);
        $this->assertInstanceOf(Container::class, $c);
    }
}