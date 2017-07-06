<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 05/07/2017
 * Time: 16:45
 */
namespace Lou117\Core\Service;

use FastRoute\Dispatcher;

class RouterProvider extends AbstractServiceProvider
{
    /**
     * @var Dispatcher
     */
    protected $router;


    public function __construct(array $services){}

    /**
     * @see AbstractServiceProvider::get()
     * @return Dispatcher
     */
    public function get()
    {
        return $this->router;
    }

    public function set(Dispatcher $router)
    {
        $this->router = $router;
        return $this;
    }
}
