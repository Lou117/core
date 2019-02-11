<?php
namespace Lou117\Core;

use FastRoute;
use \Exception;
use Monolog\Logger;
use \LogicException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Lou117\Core\Container\Container;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
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
     * @var Route[]
     */
    protected $routes = [];


    /**
     * @param string $settings_filepath - Path to settings file.
     * @param string $routing_table_filepath - Path to routing table file.
     * @throws InvalidRoutingTableException
     * @throws InvalidSettingsException
     */
    public function __construct(string $settings_filepath, string $routing_table_filepath)
    {
        $this->container = new Container();

        $this->loadSettings($settings_filepath);
        $this->initLogger();
        $this->loadRoutingTable($routing_table_filepath);

        $this->container->set("request", ServerRequest::fromGlobals());
    }

    /**
     * Runs FastRoute router.
     * @return ResponseInterface|bool - Returns TRUE or a ready-to-use instance of ResponseInterface.
     * If 'httpNotFoundResponse' is a FQCN in Core settings, given class will be used instead of an empty Response class
     * with HTTP status set to 404.
     * If 'httpNotAllowedResponse' is a FQCN in Core settings, given class will be used instead of an empty Response
     * class with 'Allowed' header set to allowed methods and HTTP status set to 405.
     * @throws LogicException - If response returned by 'httpNotFoundResponse' or 'httpNotAllowedResponse' class in
     * settings does not implements ResponseInterface.
     */
    protected function dispatch()
    {
        /**
         * @var $request RequestInterface
         * @var $settings array
         */
        $request = $this->container->get("request");
        $settings = $this->container->get("settings");

        $routerResult = $this->container->get("router")->dispatch($request->getMethod(), $request->getUri()->getPath());
        if ($routerResult[0] === FastRoute\Dispatcher::NOT_FOUND) {
            $response = class_exists($settings["httpNotFoundResponse"])
                ? new $settings["httpNotFoundResponse"]()
                : new Response();

            if (($response instanceof ResponseInterface) === false) {
                throw new LogicException("{$settings["httpNotFoundResponse"]} class must be implementing ResponseInterface");
            }

            $response = $response->withStatus(404);
            return $response;
        }

        if ($routerResult[0] === FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
            $response = class_exists($settings["httpNotAllowedResponse"])
                ? new $settings["httpNotAllowedResponse"]()
                : new Response();

            if (($response instanceof ResponseInterface) === false) {
                throw new LogicException("{$settings["httpNotAllowedResponse"]} class must be implementing ResponseInterface");
            }

            $response = $response->withStatus(405);
            $response = $response->withAddedHeader(ResponseFactory::HTTP_HEADER_ALLOW, implode(', ', $routerResult[1]));
            return $response;
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
                "prefix" => "",
                "cache" => [
                    "enabled" => true,
                    "path" => "var/cache/fastroute"
                ]
            ],
            "httpNotFoundResponse" => null,
            "httpNotAllowedResponse" => null
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

        $this->container->set("core-logger", $logger);
        return $this;
    }

    /**
     * Loads application routing table.
     * @param string $routing_table_filepath - Path to routing table file.
     * @return Core
     * @throws RoutingTableNotFoundException - If routing table is not found using given path.
     * @throws InvalidRoutingTableException - If routing table file does not return an array.
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

        $routes = RoutingTableParser::parse($loadedRoutes);

        $function = 'FastRoute\simpleDispatcher';
        $params = [];

        if (
            (bool) $this->container->get("settings")["router"]["cache"]["enabled"] === true &&
            is_writable($this->container->get("settings")["router"]["cache"]["path"])
        ) {
            $function = 'FastRoute\cachedDispatcher';
            $params = [
                'cacheFile' => $this->container->get("settings")["router"]["cache"]["path"]
            ];
        }

        $this->routes = $routes;

        $this->container->set("router", $function(function(FastRoute\RouteCollector $r) use ($routes) {

            /**
             * @var Route $routeObject
             */
            foreach ($routes as $routeIndex => $routeObject) {
                $r->addRoute($routeObject->methods, $routeObject->endpoint, $routeIndex);
            }
        }, $params));

        return $this;
    }

    /**
     * Loads Core settings.
     * @param string $settings_filepath - Path to settings file.
     * @return Core
     * @throws SettingsNotFoundException - If settings file is not found using given path.
     * @throws InvalidSettingsException - If settings file does not return an array.
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
     * @param RequestInterface $request (optional, defaults to NULL) - If an instance of RequestInterface is passed,
     * given $request will be used instead of ServerRequest created at Core instanciation. This will mostly be used by
     * tests.
     * @param bool $return_response (optional, defaults to FALSE) - If set to TRUE, generated ResponseInterface will be
     * returned instead of being sent to client and script being die()-ed. Mostly for testing purposes, but this
     * behavior can be useful if front controller is rewritten and some logic added before any response being sent.
     * @throws Exception
     * @return ResponseInterface
     */
    public function run(RequestInterface $request = null, bool $return_response = false)
    {
        try {

            if (!is_null($request)) {
                $this->container->set("request", $request);
            }

            $dispatchResult = $this->dispatch();
            if (!($dispatchResult instanceof ResponseInterface)) { // 404 Not Found or 405 Not Allowed
                $requestHandler = new RequestHandler($this->container);
                $response = $requestHandler->handle($this->container->get("request"));
            } else {
                $response = $dispatchResult;
            }

            if ($return_response === false) {
                ResponseFactory::sendToClient($response);
                die();
            } else {
                return $response;
            }
        } catch (Exception $e) {
            $this->container->get("core-logger")->error($e->getMessage());
            throw $e;
        }
    }
}
