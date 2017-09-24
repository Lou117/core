<?php
    namespace Lou117\Core;

    use FastRoute;
    use \Exception;
    use Monolog\Logger;
    use \LogicException;
    use Lou117\Core\Http\Request;
    use Composer\Autoload\ClassLoader;
    use Lou117\Core\Module\ModuleMetadata;
    use Lou117\Core\Module\AbstractModule;
    use Lou117\Core\Service\RoutingProvider;
    use Lou117\Core\Service\LoggerProvider;
    use Monolog\Handler\RotatingFileHandler;
    use Lou117\Core\Service\SettingsProvider;
    use Lou117\Core\Http\Response\TextResponse;
    use Lou117\Core\Http\Response\ProblemResponse;
    use Lou117\Core\Http\Response\AbstractResponse;
    use Lou117\Core\Service\AbstractServiceProvider;

    class Core
    {
        const ROUTES_CACHE_FILEPATH = 'cache/routes';


        /**
         * @var bool
         */
        protected static $booted = false;

        /**
         * @var ClassLoader
         */
        protected static $composerLoader;

        /**
         * HTTP request.
         * @var Request
         */
        protected static $request;

        /**
         * @var array
         */
        protected static $services = [];


        protected function __construct(){}

        protected function __sleep(){}

        protected function __wakeUp(){}

        /**
         * Core main method, to be called by public/index.php.
         * This method has an internal try-catch block, so there is no real need to surround a Core::boot call with
         * another try-catch block.
         * @param string $application_directory - application root directory path, to be used for retrieval of all
         * needed files (mainly settings) and FastRoute cache writing.
         * @param ClassLoader $composer_loader - Composer loader to be use for registering modules at runtime.
         */
        public static function boot(ClassLoader $composer_loader)
        {
            if (self::$booted) {

                throw new LogicException('Core::boot method cannot be called twice');

            }

            self::$composerLoader = $composer_loader;

            self::setService('core.settings', new SettingsProvider(self::$services));

            try {

                self::setService('core.logger', new LoggerProvider(self::$services));

                $settings = self::getService('core.settings');

                /*
                 * "Debug mode" is ability for Problem instances to carry exceptions' message instead of a generic
                 * "Internal Server Error" message.
                 */
                if (array_key_exists('debugMode', $settings) && $settings != false) {

                    Problem::$debugMode = true;

                }

                $start = microtime(true);
                $modules = self::loadModules();
                $time = round(microtime(true) - $start, 5);
                self::getService('core.logger')->info('Modules loading took '.$time.'s');

                $start = microtime(true);
                self::loadRoutes($modules);
                $time = round(microtime(true) - $start, 5);
                self::getService('core.logger')->info('Routes loading took '.$time.'s');

                if ($settings['startSession']) {

                    session_start();

                }

                self::loadServices($modules);



                /* Request processing */

                self::$request = new Request(true);

                $parsingResult = self::$request->parseRequestBody();
                if ($parsingResult instanceof ProblemResponse) {

                    $parsingResult->send();
                    return;

                }

                // Routing
                $dispatchResult = self::dispatch();
                if ($dispatchResult instanceof TextResponse) {

                    $dispatchResult->send();
                    return;

                }

                $moduleClass = $dispatchResult->module->fqcn;

                /**
                 * @var $module AbstractModule
                 */
                $module = new $moduleClass(self::$request, $dispatchResult);

                $response = $module->run();
                $response->send();

            } catch (Exception $e) {

                if (self::hasService('core.logger')) {

                    self::getService('core.logger')->critical($e->getMessage(), $e->getTrace());

                }

                $response = new ProblemResponse();
                $response->send(AbstractResponse::HTTP_500, new Problem($e));

            }

            return;
        }

        /**
         * Run FastRoute router.
         * @return Route|AbstractResponse - returns an instance of TextResponse with HTTP code automatically set if no
         * result was found for route (404) or if a route was found but HTTP method is not allowed (405).
         */
        protected static function dispatch()
        {
            $response = new TextResponse();
            $routeInfo = self::getService('core.routing')->getRouter()->dispatch(self::$request->method, self::$request->uri);
            if ($routeInfo[0] === FastRoute\Dispatcher::NOT_FOUND) {

                return $response->setStatusCode(AbstractResponse::HTTP_404);

            }

            if ($routeInfo[0] === FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {

                $allowedMethodsAsString = implode(', ', $routeInfo[1]);
                $response->addHeader(AbstractResponse::HTTP_HEADER_ALLOW, $allowedMethodsAsString);
                return $response->setStatusCode(AbstractResponse::HTTP_405);

            }

            $route = self::getService('core.routing')->getRoutes()[$routeInfo[1]];
            $route->uriData = $routeInfo[2];

            return $route;
        }

        /**
         * Returns TRUE when a service exists under given service name, FALSE otherwise.
         * @param string $service_name
         * @return bool
         */
        public static function hasService(string $service_name): bool
        {
            return array_key_exists($service_name, self::$services);
        }

        /**
         * Returns service by service name.
         * @param string $service_name
         * @return mixed
         * @throws Exception - when no service is found with given name.
         */
        public static function getService(string $service_name)
        {
            if (array_key_exists($service_name, self::$services)) {

                return self::$services[$service_name]->get();

            }

            throw new Exception("Unknown service {$service_name}");
        }

        /**
         * Loads modules declared by settings file, adding them to Composer and loading their routes (if any).
         * @return ModuleMetadata[]
         */
        protected static function loadModules(): array
        {
            $default = [
                "namespace" => null,
                "path" => null
            ];

            $modules = [];
            $settings = self::getService('core.settings');
            foreach ($settings['modules'] as $moduleName => $moduleConfig) {

                $moduleConfig = array_replace_recursive($default, $moduleConfig);

                if (!empty($moduleConfig["namespace"]) && !empty($moduleConfig["path"])) {

                    self::$composerLoader->addPsr4($moduleConfig["namespace"], $moduleConfig["path"]);

                }

                $moduleMetadata = new ModuleMetadata();
                $moduleMetadata->name = $moduleName;
                $moduleMetadata->fqcn = $moduleConfig["namespace"]."Module";
                $moduleMetadata->namespace = $moduleConfig["namespace"];

                $modules[$moduleName] = $moduleMetadata;

            }

            return $modules;
        }

        /**
         * Loads module routes, caching it if necessary, and set FastRoute dispatcher up.
         * By default, Core will use FastRoute\simpleDispatcher function, but if cache/ directory exists and is
         * writable, FastRoute\cachedDispatcher will be preferred. FastRoute router and loaded routes are registered as
         * a service through RoutingProvider.
         * @param ModuleMetadata[] $modules
         * @return bool
         */
        protected static function loadRoutes(array $modules): bool
        {
            if (!file_exists(self::ROUTES_CACHE_FILEPATH)) {

                self::getService('core.logger')->info('Generating routes');

                $defaultRoute = [
                    "endpoint" => "",
                    "methods" => []
                ];

                $routes = [];
                foreach ($modules as $moduleMetadata) {

                    $moduleRoutes = forward_static_call([$moduleMetadata->fqcn, 'getRoutes']);
                    foreach ($moduleRoutes as $routeName => $routeConfig) {

                        $routeConfig = array_replace($defaultRoute, $routeConfig);

                        $route = new Route();
                        $route->name = $routeName;
                        $route->module = $moduleMetadata;
                        $route->endpoint = $routeConfig["endpoint"];
                        $route->fullname = $moduleMetadata->name.".".$routeName;
                        $route->allowedMethods = $routeConfig['methods'];

                        $routes[$route->fullname] = $route;

                    }

                }

                file_put_contents(self::ROUTES_CACHE_FILEPATH, serialize($routes));

            } else {

                $routes = unserialize(file_get_contents(self::ROUTES_CACHE_FILEPATH));
                self::getService('core.logger')->info('Routes loaded from cache');

            }

            $function = 'FastRoute\simpleDispatcher';
            $params = array();

            $cacheDir = 'cache';
            $cacheFile = $cacheDir.'/fastroute';
            if (is_dir($cacheDir) && is_writable($cacheDir)) {

                if (!file_exists($cacheFile) || is_writable($cacheFile)) {

                    $function = 'FastRoute\cachedDispatcher';
                    $params = [
                        'cacheFile' => $cacheFile
                    ];

                }

            }

            $router = $function(function(FastRoute\RouteCollector $r) use ($routes) {

                foreach ($routes as $routeObject) {

                    $r->addRoute($routeObject->allowedMethods, $routeObject->endpoint, $routeObject->fullname);

                }

            }, $params);

            $routerProvider = new RoutingProvider(self::$services);
            $routerProvider
                ->setRouter($router)
                ->setRoutes($routes);

            self::setService('core.routing', $routerProvider);

            return true;
        }

        /**
         * Loads services declared by modules.
         * @param ModuleMetadata[] $modules
         * @return bool
         */
        protected static function loadServices(array $modules): bool
        {
            foreach ($modules as $moduleMetadata) {

                $services = forward_static_call([$moduleMetadata->fqcn, 'getServices']);
                foreach ($services as $serviceName => $serviceProvider) {

                    $serviceProviderFqcn = $moduleMetadata->namespace.$serviceProvider;
                    self::setService($serviceName, new $serviceProviderFqcn(self::$services));

                }

            }

            return true;
        }

        /**
         * Registers a new service provider under given service name.
         * @param string $service_name - service name used by third parties as an identifier.
         * @param AbstractServiceProvider $service_provider
         * @return bool
         * @throws Exception - when a service is already registered using given name.
         */
        protected static function setService(string $service_name, AbstractServiceProvider $service_provider): bool
        {
            if (array_key_exists($service_name, self::$services)) {

                throw new Exception("Service name conflict ({$service_name})");

            }

            self::$services[$service_name] = $service_provider;
            return true;
        }
    }
