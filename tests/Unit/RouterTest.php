<?php

namespace Tests\Unit;

use App\Http\Controllers\HomeController;
use PHPMini\Router\Route;
use PHPMini\Router\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    /** @test */
    public function it_added_a_route()
    {
        $router = new Router();
        $router->url = '/';
        $router->addRoute('/', [HomeController::class, "welcome"], ["GET", "HEAD"]);
        
        $route = new Route(["GET", "HEAD"],'/',[HomeController::class, "welcome"]);
        $expected = [
            'GET' => [$route],
            'HEAD' => [$route]
        ];
        $this->assertEquals($expected, $router->routes);
    }
    
    /** @test */
    public function it_added_a_get_route(){
        $router = new Router();
        $router->url = '/';
        $router->get('/', [HomeController::class, "welcome"]);
    
        $route = new Route(["GET", "HEAD"],'/',[HomeController::class, "welcome"]);
        $this->assertEquals($route, $router->routes['GET'][0]);
    }
    
    /** @test */
    public function not_route_created()
    {
        $this->assertEmpty((new Router())->routes);
    }
    
//    /** @test */
    public function it_added_get_route_from_a_closure()
    {
        $router = new Router();
        
        $router->get('/', fn()=>true);
        $this->assertEquals(true, $router->run('/', 'GET'));
    }
}