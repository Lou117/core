<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 05/07/2017
 * Time: 16:45
 */
namespace Lou117\Core\Service;

use Lou117\Core\Route;
use FastRoute\Dispatcher;

class RoutingProvider extends AbstractServiceProvider
{
    /**
     * @var Dispatcher
     */
    protected $router;

    /**
     * @var Route[]
     */
    protected $routes;


    public function __construct(array $services){}

    /**
     * @see AbstractServiceProvider::get()
     * @return RoutingProvider
     */
    public function get()
    {
        return $this;
    }

    /**
     * @return Dispatcher
     */
    public function getRouter(): Dispatcher
    {
        return $this->router;
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param Dispatcher $router
     * @return $this
     */
    public function setRouter(Dispatcher $router): RoutingProvider
    {
        $this->router = $router;
        return $this;
    }

    /**
     * @param Route[] $routes
     * @return $this
     */
    public function setRoutes(array $routes): RoutingProvider
    {
        $this->routes = $routes;
        return $this;
    }
}
