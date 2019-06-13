<?php
namespace Lou117\Core;

use FastRoute;
use \Exception;
use Monolog\Logger;
use \LogicException;
use \RuntimeException;
use GuzzleHttp\Psr7\Response;
use Lou117\Core\Routing\Route;
use \InvalidArgumentException;
use GuzzleHttp\Psr7\ServerRequest;
use Lou117\Core\Container\Container;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Lou117\Core\Routing\NestedTableParser;
use Lou117\Core\Routing\AbstractTableParser;

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
     * @param string|null $configuration_file_path - [optional] configuration file path (defaults to NULL).
     * @param string|null $routing_table_file_path - [optional] routing table file path (defaults to NULL).
     */
    public function __construct(string $configuration_file_path = null, string $routing_table_file_path = null)
    {
        $this->container = new Container();
        $this->container->set("core.request", ServerRequest::fromGlobals());

        if (
            is_null($configuration_file_path) === false
            && trim($configuration_file_path) !== ""
        ) {
            $this->loadConfigurationFile($configuration_file_path);
        }

        if (
            is_null($routing_table_file_path) === false
            && trim($routing_table_file_path) !== ""
        ) {
            $this->loadRoutingTableFile($routing_table_file_path);
        }
    }

    /**
     * Builds a instance of Monolog\Logger class, using current settings, and registers it in Core internal PSR-11
     * container as Core main logger.
     *
     * @return Core
     */
    protected function buildLogger(): self
    {
        $settings = $this->container->get("core.configuration");

        $logger = new Logger($settings["logger"]["channel"]);
        $logger->pushHandler(new $settings["logger"]["class"][0](...$settings["logger"]["class"][1]));

        $this->container->set("core.logger", $logger);
        return $this;
    }

    /**
     * Builds an instance of FastRoute\Dispatcher interface, and loads current Core routing table in it.
     *
     * @return Core
     */
    protected function buildRouter(): self
    {
        $settings = $this->container->get("core.configuration");

        /* Applying prefix */

        $prefix = trim($settings["router"]["prefix"]);
        $this->routes = array_map(function(Route $route) use ($prefix){
            $route->endpoint = $prefix.$route->endpoint;

            if (substr($route->endpoint, 0, 1) !== "/") {
                $route->endpoint = "/{$route->endpoint}";
            }

            return $route;
        }, $this->routes);

        /* Configuring FastRoute */

        $function = 'FastRoute\simpleDispatcher';
        $params = [];

        if (
            (bool) $settings["router"]["cache"]["enabled"] === true &&
            is_writable($settings["router"]["cache"]["path"])
        ) {
            $function = 'FastRoute\cachedDispatcher';
            $params = [
                'cacheFile' => $settings["router"]["cache"]["path"]
            ];
        }

        /* Feeding FastRoute */

        $this->container->set("core.router", $function(function(FastRoute\RouteCollector $r) use ($prefix) {

            /**
             * @var Route $routeObject
             */
            foreach ($this->routes as $routeIndex => $routeObject) {
                $r->addRoute($routeObject->methods, $prefix.$routeObject->endpoint, $routeIndex);
            }
        }, $params));

        return $this;
    }

    /**
     * Runs FastRoute router.
     *
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
        $request = $this->container->get("core.request");
        $settings = $this->container->get("core.configuration");

        $routerResult = $this->container->get("core.router")->dispatch($request->getMethod(), $request->getUri()->getPath());
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

        $this->container->set("core.route", $route);
        return true;
    }

    /**
     * Returns Core internal PSR-11 container.
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Returns Core default settings.
     *
     * @return array
     */
    public static function getDefaultSettings(): array
    {
        return [
            "logger" => [
                "channel" => "core",
                "class" => ["Monolog\Handler\RotatingFileHandler", ["var" . DIRECTORY_SEPARATOR . "log" . DIRECTORY_SEPARATOR . "log", 10]]
            ],
            "mw-sequence" => [],
            "router" => [
                "prefix" => "",
                "parser" => NestedTableParser::class,
                "cache" => [
                    "enabled" => true,
                    "path" => "var" . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR . "fastroute"
                ]
            ],
            "httpNotFoundResponse" => null,
            "httpNotAllowedResponse" => null
        ];
    }

    /**
     * Loads file located at given $configuration_file_path as new configuration file for Core instance.
     *
     * @param string $configuration_file_path - configuration file path.
     * @return Core
     * @throws RuntimeException - if file located at given $configuration_file_path is not a PHP script returning an
     * array.
     * @throws InvalidArgumentException - if no file is found at given $configuration_file_path.
     */
    public function loadConfigurationFile(string $configuration_file_path): self
    {
        if (file_exists($configuration_file_path) === false) {
            throw new InvalidArgumentException("File <{$configuration_file_path}> not found");
        }

        $configuration = require($configuration_file_path);

        if (is_array($configuration) === false) {
            throw new RuntimeException("File <{$configuration}> does not return an PHP array");
        }

        $this->container->set("core.configuration", array_replace_recursive(self::getDefaultSettings(), $configuration));
        $this->buildLogger();

        return $this;
    }

    /**
     * Loads file located at given $routing_table_file_path as new routing table for Core instance.
     *
     * @param string $routing_table_file_path - routing table file path.
     * @return Core
     * @throws InvalidArgumentException - if no file is found at given $routing_table_file_path.
     * @throws LogicException - if routing table parsing class set in Core configuration does not extends
     * AbstractTableParser class.
     */
    public function loadRoutingTableFile(string $routing_table_file_path): self
    {
        if (file_exists($routing_table_file_path) === false) {
            throw new InvalidArgumentException("File <{$routing_table_file_path}> not found");
        }

        $routingTableParserClass = $this->container->get("core.configuration")["router"]["parser"];

        if (is_a($routingTableParserClass, AbstractTableParser::class, true) === false) {
            throw new LogicException("Routing table parsing class {$routingTableParserClass} does not implements AbstractTableParser");
        }

        /**
         * @var AbstractTableParser $routingTableParser
         */
        $routingTableParser = new $routingTableParserClass($this->container->get("core.logger"));

        $this->routes = array_filter($routingTableParser->parse($routing_table_file_path), function ($candidate) use ($routingTableParserClass) {

            if (($candidate instanceof Route) === false) {
                $this->container->get("core.logger")->info("Entry returned by <{$routingTableParserClass}> is not an instance of Route class and is ignored");
                return false;
            } else {
                return true;
            }
        });

        return $this;
    }

    /**
     * Core main method, to be called by entry script.
     *
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
                $this->container->set("core.request", $request);
            }

            // FastRoute is "built" only when Core::run() is called, after all routes have been loaded.
            $this->buildRouter();

            $dispatchResult = $this->dispatch();
            if (!($dispatchResult instanceof ResponseInterface)) { // 404 Not Found or 405 Not Allowed
                $requestHandler = new RequestHandler($this->container);
                $response = $requestHandler->handle($this->container->get("core.request"));
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
            $this->container->get("core.logger")->error($e->getMessage());
            throw $e;
        }
    }
}
