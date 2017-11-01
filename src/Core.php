<?php
    namespace Lou117\Core;

    use FastRoute;
    use \Exception;
    use Monolog\Logger;
    use \InvalidArgumentException;
    use GuzzleHttp\Psr7\ServerRequest;
    use Lou117\Core\Http\Response\EmptyResponse;
    use Lou117\Core\Http\Response\AbstractResponse;
    use Lou117\Core\Exception\RoutesNotFoundException;
    use Lou117\Core\Exception\SettingsNotFoundException;

    /**
     * Class Core
     * @package Lou117\Core
     * @property Logger $logger
     * @property ServerRequest $request
     * @property array $settings
     */
    class Core
    {
        /**
         * Core internal data storage.
         * @var array
         */
        protected $store = [
            "logger"    => null,
            "request"   => null,
            "settings"  => null
        ];

        /**
         * @var FastRoute\Dispatcher
         */
        protected $router;

        /**
         * @var Route[]
         */
        protected $routes = [];


        /**
         * Core constructor, to be called by entry script.
         *
         * @param string $settings_filepath - Path to settings file.
         * @param string $routes_filepath - Path to routes file.
         */
        public function __construct(string $settings_filepath, string $routes_filepath)
        {
            $this->loadSettings($settings_filepath);
            $this->initLogger();
            $this->loadRoutes($routes_filepath);

            $this->store["request"] = ServerRequest::fromGlobals();
        }

        /**
         * Core main method, to be called by entry script.
         *
         * @throws Exception
         */
        public function run()
        {
            try {

                $dispatchResult = $this->dispatch();
                if ($dispatchResult instanceof EmptyResponse) {

                    $dispatchResult->send();
                    die();

                }

                /**
                 * @var Route $route
                 */
                $route = $this->store["request"]->getAttribute("route");

                $controllerData = explode("::", $route->controller);

                /**
                 * @var $controller AbstractController
                 */
                $controller = new $controllerData[0]($this);
                $controller->run($controllerData[1])->send();

            } catch (Exception $e) {

                $this->store["logger"]->error($e->getMessage());
                throw $e;

            }
        }

        /**
         * Run FastRoute router.
         *
         * @return AbstractResponse|bool - Returns true or a ready-to-use instance of EmptyResponse with HTTP code set
         * if no result was found (404) or if a route was found but HTTP method is not allowed (405).
         */
        protected function dispatch()
        {
            /**
             * @var $request ServerRequest
             */
            $request = $this->store["request"];
            $response = new EmptyResponse();

            $routerResult = $this->router->dispatch($request->getMethod(), $request->getUri()->getPath());
            if ($routerResult[0] === FastRoute\Dispatcher::NOT_FOUND) {

                return $response->setStatus(404);

            }

            if ($routerResult[0] === FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {

                $allowedMethodsAsString = implode(', ', $routerResult[1]);
                $response->addHeader(AbstractResponse::HTTP_HEADER_ALLOW, $allowedMethodsAsString);
                return $response->setStatus(405);

            }

            $route = $this->routes[$routerResult[1]];
            $route->arguments = array_replace_recursive($route->arguments, $routerResult[2]);

            $this->store["request"] = $request->withAttribute("route", $route);

            return true;
        }

        /**
         * Initializes Core logger.
         * @return Core
         */
        protected function initLogger(): Core
        {
            $settings = $this->store["settings"];
            $logger = new Logger($settings["log"]["channel"]);
            $logger->pushHandler(new $settings["log"]["class"][0](...$settings["log"]["class"][1]));

            $this->store["logger"] = $logger;

            return $this;
        }

        /**
         * Loads application routes.
         * @param string $routes_filepath - Routes file path.
         * @return Core
         */
        protected function loadRoutes(string $routes_filepath): Core
        {
            $loadedRoutes = $this->requireFile($routes_filepath);
            if ($loadedRoutes === null) {

                throw new RoutesNotFoundException();

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

            if (is_writable($this->store["settings"]["routerCachePath"])) {

                $function = 'FastRoute\cachedDispatcher';
                $params = [
                    'cacheFile' => $this->store["settings"]["routerCachePath"]
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
         * Loads Core settings.
         * @param string $settings_filepath - Settings file path.
         * @return Core
         */
        protected function loadSettings(string $settings_filepath): Core
        {
            $settings = $this->requireFile($settings_filepath);
            if ($settings === null) {

                throw new SettingsNotFoundException();

            }

            $this->store["settings"] = $this->setDefaultSettings($settings);
            return $this;
        }

        /**
         * Requires the file located at given $filepath using require(). Required file must return an array.
         * @param string $filepath - Path to file that must be required.
         * @return array|null
         */
        protected function requireFile(string $filepath): ?array
        {
            if (!file_exists($filepath)) {

                return null;

            }

            return require($filepath);
        }

        /**
         * Applies default settings to given loaded settings, ensuring that some critical settings values are provided.
         * @param array $loaded_settings - Loaded settings.
         * @return array
         */
        protected function setDefaultSettings(array $loaded_settings): array
        {
            return array_replace_recursive([
                "log" => [
                    "channel" => "core",
                    "class" => ["Monolog\Handler\RotatingFileHandler", ["var/log/", 10]]
                ],
                "routerCachePath" => "var/cache/fastroute"
            ], $loaded_settings);
        }

        /**
         * Allows read-only access to Core settings, routes and logger.
         * @param string $name - Can be "settings", "routes" or "logger".
         * @return mixed
         */
        public function __get(string $name)
        {
            if (!array_key_exists($name, $this->store)) {

                throw new InvalidArgumentException("Core has no available property {$name}");

            }

            return $this->store[$name];
        }
    }
