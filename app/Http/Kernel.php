<?php

namespace App\Http;

use PHPMini\Routing\Router;
use PHPMini\Requests\Request;
use PHPMini\Application\Application;

class Kernel
{
    /**
     * The router instance
     *  @var Router
     */
    protected $router;

    /**
     * The application instance
     * 
     * @var Application
     */
    protected $app;


    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->router = $app->get('router');
    }

    /**
     * Handle an incoming HTTP Request
     * 
     * @param \PHPMini\Requests\Request $request
     */
    function handle($request)
    {
        $this->app->instance('request', $request);
        return $this->router->dispatch($request);
    }
}
