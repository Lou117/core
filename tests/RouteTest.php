<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 08/07/2018
 * Time: 19:52
 */
use Lou117\Core\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testRouteInstantiationHasNoParameter()
    {
        $route = new Route();
        $this->assertInstanceOf(Route::class, $route);
        return $route;
    }

    /**
     * @param Route $route
     * @depends testRouteInstantiationHasNoParameter
     */
    public function testRouteAttributes(Route $route)
    {
        $this->assertObjectHasAttribute("arguments", $route);
        $this->assertObjectHasAttribute("attributes", $route);
        $this->assertObjectHasAttribute("controller", $route);
        $this->assertObjectHasAttribute("endpoint", $route);
        $this->assertObjectHasAttribute("methods", $route);
        $this->assertObjectHasAttribute("name", $route);
    }
}
