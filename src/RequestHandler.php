<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 08/07/2018
 * Time: 16:38
 */
namespace Lou117\Core;

use \LogicException;
use \RuntimeException;
use \BadMethodCallException;
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
        reset($this->middlewareSequence);
    }

    /**
     * {@inheritdoc}
     * @return ResponseInterface
     * @throws RuntimeException
     * @throws BadMethodCallException
     * @throws LogicException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middlewareEntry = current($this->middlewareSequence);
        next($this->middlewareSequence);

        // If middleware sequence is exhausted, controller is instantiated and run.
        if ($middlewareEntry === false) {

            /**
             * @var Route $route
             */
            $route = $this->container->get("route");

            $controllerData = explode("::", $route->controller);
            if (count($controllerData) !== 2) {

                throw new RuntimeException("Invalid controller declaration for route {$route->name} (expecting '\\Namespace\\Class::method' format)");

            }

            $controllerClass = $controllerData[0];
            if (class_exists($controllerClass) === false) {

                throw new RuntimeException("Invalid controller declaration for route {$route->name} (unknown class {$controllerClass})");

            }

            /**
             * @var $controller AbstractController
             */
            $controller = new $controllerClass($this->container);

            $controllerMethod = $controllerData[1];
            if (method_exists($controller, $controllerMethod) === false) {

                throw new BadMethodCallException("{$controllerClass}::{$controllerMethod} declared in routes doesn't exists in {$controllerClass}");

            }

            /**
             * @var $response ResponseInterface
             */
            $response = $controller->{$controllerMethod}();
            if (($response instanceof ResponseInterface) === false) {

                throw new LogicException("Method {$controllerMethod} must return an instance of PSR-7 ResponseInterface");

            }

            return $response;

        } else {

            $middlewareFQCN = is_array($middlewareEntry) ? $middlewareEntry[0] : $middlewareEntry;
            $middlewareParams = is_array($middlewareEntry) && count($middlewareEntry) > 1 ? $middlewareEntry[1] : null;

            /**
             * @var $middleware MiddlewareInterface
             */
            $middleware = new $middlewareFQCN($middlewareParams);
            if (($middleware instanceof MiddlewareInterface) === false) {

                throw new LogicException("Middleware {$middlewareFQCN} must implements PSR-11 MiddlewareInterface");

            }

            return $middleware->process($request, $this);

        }
    }
}
