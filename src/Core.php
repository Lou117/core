<?php
namespace Lou117\Core;

use FastRoute;
use \Exception;
use Monolog\Logger;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Lou117\Core\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Lou117\Core\Exception\InvalidSettingsException;
use Lou117\Core\Exception\SettingsNotFoundException;
use Lou117\Core\Exception\InvalidRoutingTableException;
use Lou117\Core\Exception\RoutingTableNotFoundException;

/**
 * Class Core
 * @package Lou117\Core
 */
class Core
{
    /**
     * Core internal PSR-11 container.
     * @var Container
     */
    protected $container;

    /**
     * @var FastRoute\Dispatcher
     */
    protected $router;

    /**
     * @var Route[]
     */
    protected $routes = [];


    /**
     * @param string $settings_filepath
     * @param string $routing_table_filepath
     * @throws InvalidRoutingTableException
     * @throws InvalidSettingsException
     */
    public function __construct(string $settings_filepath, string $routing_table_filepath)
    {
        $this->loadSettings($settings_filepath);
        $this->initLogger();
        $this->loadRoutingTable($routing_table_filepath);

        $this->container->set("request", ServerRequest::fromGlobals());
    }

    /**
     * Run FastRoute router.
     *
     * @return ResponseInterface|bool - Returns true or a ready-to-use instance of EmptyResponse with HTTP code set
     * if no result was found (404) or if a route was found but HTTP method is not allowed (405).
     */
    protected function dispatch()
    {
        /**
         * @var $request ServerRequest
         */
        $request = $this->container->get("request");

        $routerResult = $this->router->dispatch($request->getMethod(), $request->getUri()->getPath());
        if ($routerResult[0] === FastRoute\Dispatcher::NOT_FOUND) {

            return new Response(404);

        }

        if ($routerResult[0] === FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {

            $allowedMethodsAsString = implode(', ', $routerResult[1]);
            return new Response(405, [
                ResponseFactory::HTTP_HEADER_ALLOW => $allowedMethodsAsString
            ]);

        }

        $route = $this->routes[$routerResult[1]];
        $route->arguments = array_replace_recursive($route->arguments, $routerResult[2]);

        $this->container->set("route", $route);

        return true;
    }

    /**
     * Applies default settings to given loaded settings, ensuring that some critical settings values are provided.
     * @param array $loaded_settings - Loaded settings.
     * @return array
     */
    protected function ensureDefaultSettings(array $loaded_settings): array
    {
        return array_replace_recursive([
            "logger" => [
                "channel" => "core",
                "class" => ["Monolog\Handler\RotatingFileHandler", ["var/log/log", 10]]
            ],
            "mw-sequence" => [],
            "router" => [
                "enabled" => true,
                "cache" => "var/cache/fastroute"
            ],
        ], $loaded_settings);
    }

    /**
     * Returns Core internal PSR-11 container.
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Initializes Core logger.
     * @return Core
     */
    protected function initLogger(): self
    {
        $settings = $this->container->get("settings");

        $logger = new Logger($settings["logger"]["channel"]);
        $logger->pushHandler(new $settings["logger"]["class"][0](...$settings["logger"]["class"][1]));

        $this->container->set("logger", $logger);
        return $this;
    }

    /**
     * Loads application routing table.
     * @param string $routing_table_filepath
     * @return Core
     * @throws InvalidRoutingTableException
     */
    protected function loadRoutingTable(string $routing_table_filepath): Core
    {
        if (file_exists($routing_table_filepath) === false) {

            throw new RoutingTableNotFoundException();

        }

        $loadedRoutes = require($routing_table_filepath);
        if (is_array($loadedRoutes) === false) {

            throw new InvalidRoutingTableException();

        }

        $defaultRouteConfig = [
            "methods" => [],
            "endpoint" => null,
            "arguments" => [],
            "controller" => null
        ];

        $routes = [];
        foreach ($loadedRoutes as $routeName => $routeConfig) {

            $routeConfig = array_replace_recursive($defaultRouteConfig, $routeConfig);

            $route = new Route();
            $route->name = $routeName;
            $route->methods = $routeConfig["methods"];
            $route->endpoint = $routeConfig["endpoint"];
            $route->arguments = $routeConfig["arguments"];
            $route->controller = $routeConfig["controller"];

            unset(
                $routeConfig["endpoint"],
                $routeConfig["methods"],
                $routeConfig["arguments"],
                $routeConfig["controller"]
            );

            $route->attributes = $routeConfig;
            $routes[$route->name] = $route;
        }

        $function = 'FastRoute\simpleDispatcher';
        $params = [];

        if (
            (bool) $this->container->get("settings")["router"]["enabled"] === true &&
            is_writable($this->container->get("settings")["router"]["cache"])
        ) {

            $function = 'FastRoute\cachedDispatcher';
            $params = [
                'cacheFile' => $this->container->get("settings")["router"]["cache"]
            ];

        }

        $this->routes = $routes;

        $this->router = $function(function(FastRoute\RouteCollector $r) use ($routes) {

            /**
             * @var Route $routeObject
             */
            foreach ($routes as $routeObject) {

                $r->addRoute($routeObject->methods, $routeObject->endpoint, $routeObject->name);

            }

        }, $params);

        return $this;
    }

    /**
     * Loads Core settings
     * @param string $settings_filepath
     * @return Core
     * @throws InvalidSettingsException
     */
    protected function loadSettings(string $settings_filepath): Core
    {
        if (file_exists($settings_filepath) === false) {

            throw new SettingsNotFoundException();

        }

        $settings = require($settings_filepath);
        if (is_array($settings) === false) {

            throw new InvalidSettingsException();

        }

        $this->container->set("settings", $this->ensureDefaultSettings($settings));
        return $this;
    }

    /**
     * Core main method, to be called by entry script.
     * @throws Exception
     */
    public function run()
    {
        try {

            $dispatchResult = $this->dispatch();
            if ($dispatchResult instanceof ResponseInterface) {

                $response = $dispatchResult;
                ResponseFactory::sendToClient($response);

            }

            $requestHandler = new RequestHandler($this->container);
            ResponseFactory::sendToClient($requestHandler->handle($this->container->get("request")));

            die();

        } catch (Exception $e) {

            $this->container->get("logger")->error($e->getMessage());
            throw $e;

        }
    }

    /**
     * Sets an alternative request, explicitly replacing request built from server globals at Core instantiation. This
     * method is mainly provided for testing purposes, when a mocked request must be used.
     *
     * Please note that this method can no longer be used after Core::run() method has been called, because of Core
     * internal container being "protected". See Container class documentation.
     * @param ServerRequestInterface $request
     * @return Core
     */
    public function setRequest(ServerRequestInterface $request): self
    {
        $this->container->set("request", $request);
        return $this;
    }
}
