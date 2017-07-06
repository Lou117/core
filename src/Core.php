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
    use Lou117\Core\Service\RouterProvider;
    use Lou117\Core\Service\LoggerProvider;
    use Monolog\Handler\RotatingFileHandler;
    use Lou117\Core\Service\SettingsProvider;
    use Lou117\Core\Http\Response\TextResponse;
    use Lou117\Core\Http\Response\ProblemResponse;
    use Lou117\Core\Http\Response\AbstractResponse;
    use Lou117\Core\Service\AbstractServiceProvider;

    class Core
    {
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
         * Core own HTTP response.
         * @var TextResponse
         */
        protected static $response;

        /**
         * Routing table.
         * @var [Route]
         */
        protected static $routes;

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

            self::$response = new TextResponse();
            self::$composerLoader = $composer_loader;

            try {

                self::setService('core.settings', new SettingsProvider(self::$services));
                self::setService('core.logger', new LoggerProvider(self::$services));

                // Debug mode
                $settings = self::$services['core.settings'];
                if (array_key_exists('debugMode', $settings) && $settings != false) {

                    Problem::$debugMode = true;

                }

                self::loadModules();



                /* Request processing */

                self::$request = new Request(true);

                $parsingResult = self::$request->parseRequestBody();
                if ($parsingResult === Request::PARSE_405) {

                    self::$response->send(AbstractResponse::HTTP_405);
                    return;

                }

                if ($parsingResult === Request::PARSE_415) {

                    self::$response->send(AbstractResponse::HTTP_415);
                    return;

                }

                // Routing
                $route = self::dispatch();
                if (!($route instanceof Route)) {

                    self::$response->send();
                    return;

                }

                session_start();

                $moduleClass = $route->module->composerNamespace.'Module';

                /**
                 * @var $module AbstractModule
                 */
                $module = new $moduleClass(self::$request, $route);

                $response = $module->run();
                $response->send();

            } catch (Exception $e) {

                if (self::getService('core.logger') instanceof Logger) {

                    self::getService('core.logger')->critical($e->getMessage());

                }

                $response = new ProblemResponse();
                $response->send(AbstractResponse::HTTP_500, new Problem($e));

            }

            return;
        }

        /**
         * Initializes and run FastRoute router. By default, Core will use FastRoute\simpleDispatcher function, but if
         * cache/ directory exists and is writable, FastRoute\cachedDispatcher will be preferred.
         * FastRoute router is registered as a service providing FastRoute functions to third parties.
         * @return Route|bool - returns FALSE if no result was found for route (404) or if a route was found but HTTP
         * method is not allowed (405). In both cases, Core::$response property will be updated accordingly.
         */
        protected static function dispatch()
        {
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

            $routes = self::$routes;

            /**
             * @var FastRoute\Dispatcher $dispatcher
             */
            $router = $function(function(FastRoute\RouteCollector $r) use ($routes) {

                foreach ($routes as $routeObject) {

                    $r->addRoute($routeObject->allowedMethods, $routeObject->endpoint, $routeObject->fullname);

                }

            }, $params);

            $routeInfo = $router->dispatch(self::$request->method, self::$request->uri);
            if ($routeInfo[0] === FastRoute\Dispatcher::NOT_FOUND) {

                self::$response->setStatusCode(AbstractResponse::HTTP_404);
                return false;

            }

            if ($routeInfo[0] === FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {

                $allowedMethodsAsString = implode(', ', $routeInfo[1]);

                self::$response->addHeader(AbstractResponse::HTTP_HEADER_ALLOW, $allowedMethodsAsString);
                self::$response->setStatusCode(AbstractResponse::HTTP_405);
                return false;

            }

            $routerProvider = new RouterProvider(self::$services);
            $routerProvider->set($router);

            self::setService('core.router', $routerProvider);

            $route = self::$routes[$routeInfo[1]];
            $route->uriData = $routeInfo[2];

            return $route;
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
         * @return bool
         */
        protected static function loadModules(): bool
        {
            $default = [
                "namespace" => null,
                "path" => null,
                "routes" => null,
                "services" => []
            ];

            $settings = self::getService('core.settings');
            foreach ($settings['modules'] as $moduleName => $moduleConfig) {

                $moduleConfig = array_replace_recursive($default, $moduleConfig);

                $module = new ModuleMetadata();
                $module->name = $moduleName;
                $module->routes = $moduleConfig['routes'];
                $module->composerPath = $moduleConfig['path'];
                $module->composerNamespace = $moduleConfig['namespace'];

                if (!empty($module->composerNamespace) && !empty($module->composerPath)) {

                    self::$composerLoader->addPsr4($module->composerNamespace, getcwd().$module->composerPath);

                }

                if (!empty($module->routes) && file_exists(getcwd().$module->routes)) {

                    $moduleRoutes = require(getcwd().$module->routes);
                    if (is_array($moduleRoutes)) {

                        self::loadRoutes($module, $moduleRoutes);

                    }

                }

                if (is_array($moduleConfig['services'])) {

                    foreach ($moduleConfig['services'] as $service_name => $service_provider) {

                        self::setService($service_name, new $service_provider(self::$services));

                    }

                }

            }

            return true;
        }

        /**
         * Loads module routes, adding them to internal routing table.
         * @param ModuleMetadata $module - Module configuration.
         * @param array $routes - Module routes.
         * @return bool
         */
        protected static function loadRoutes(ModuleMetadata $module, array $routes) {

            $default = [
                'allowedMethods' => [],
                'endpoint' => ''
            ];

            foreach ($routes as $routeName => $routeConfig) {

                $routeConfig = array_replace_recursive($default, $routeConfig);

                $route = new Route();
                $route->name = $routeName;
                $route->module = $module;
                $route->endpoint = $routeConfig['endpoint'];
                $route->fullname = $module->name.'.'.$route->name;
                $route->allowedMethods = $routeConfig['allowedMethods'];

                self::$routes[$route->fullname] = $route;

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
