<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 08/07/2018
 * Time: 16:38
 */
namespace Lou117\Core;

use Lou117\Core\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface
{
    /**
     * Reference to Core container.
     * @var Container
     */
    public $container;

    /**
     * Middleware sequence from first layer to last layer.
     * @var MiddlewareInterface[]
     */
    protected $middlewareSequence = [];


    /**
     * @param Container $core_container
     */
    public function __construct(Container $core_container)
    {
        $this->container = $core_container;
        $this->middlewareSequence = $this->container->get("settings")["mw-sequence"];
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = current($this->middlewareSequence);
        next($this->middlewareSequence);

        if ($middleware instanceof MiddlewareInterface) {

            $response = $middleware->process($request, $this);

        }

        if (!isset($response)) {

            /**
             * @var Route $route
             */
            $route = $this->container->get("route");

            $controllerData = explode("::", $route->controller);

            /**
             * @var $controller AbstractController
             */
            $controller = new $controllerData[0]($this->container);
            $response = $controller->run($controllerData[1]);

        }

        return $response;
    }
}
