<?php
    namespace Lou117\Core;

    use \Exception;
    use FastRoute;
    use Monolog\Logger;
    use \LogicException;
    use Lou117\Core\Http\Request;
    use Composer\Autoload\ClassLoader;
    use Lou117\Core\Module\ModuleMetadata;
    use Lou117\Core\Module\AbstractModule;
    use Monolog\Handler\RotatingFileHandler;
    use Lou117\Core\Http\Response\TextResponse;
    use Lou117\Core\Http\Response\ProblemResponse;
    use Lou117\Core\Http\Response\AbstractResponse;
    use Lou117\Core\Exception\SettingsNotFoundException;

    class Core
    {
        /**
         * Application root directory path.
         * @var string
         */
        protected static $applicationDirectory;

        /**
         * @var bool
         */
        protected static $booted = false;

        /**
         * @var ClassLoader
         */
        protected static $composerLoader;

        /**
         * @var
         */
        protected static $logger;

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
         * @var FastRoute\Dispatcher
         */
        protected static $router;

        /**
         * Routing table.
         * @var [Route]
         */
        protected static $routes;

        /**
         * Core global settings.
         * @var array
         */
        protected static $settings;


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
        public static function boot(string $application_directory, ClassLoader $composer_loader)
        {
            if (self::$booted) {

                throw new LogicException('Core::boot method cannot be called twice');

            }

            self::$response = new TextResponse();
            self::$composerLoader = $composer_loader;

            try {

                self::$applicationDirectory = $application_directory;
                if (substr(self::$applicationDirectory, -1, 1) !== '/') {

                    self::$applicationDirectory .= '/';

                }



                /* Settings processing */

                self::fetchSettings();



                /* Logger initialization */

                self::$logger = new Logger(self::$settings['logChannel']);
                self::$logger->pushHandler(new RotatingFileHandler(self::$applicationDirectory.'log/core', 10));


                /* Debug mode */

                if (array_key_exists('debugMode', self::$settings) && self::$settings['debugMode'] != false) {

                    self::$logger->info('Debug mode activated');
                    Problem::$debugMode = true;

                }



                /* Modules loading */

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

                self::$logger->critical($e->getMessage());

                $response = new ProblemResponse();
                $response->send(AbstractResponse::HTTP_500, new Problem($e));

            }

            return;
        }

        /**
         * Initializes and run FastRoute router. By default, Core will use FastRoute\simpleDispatcher function, but if
         * cache/ directory exists and is writable, FastRoute\cachedDispatcher will be preferred.
         * @return Route|bool - returns FALSE if no result was found for route (404) or if a route was found but HTTP
         * method is not allowed (405). In both cases, Core::$response property will be updated accordingly.
         */
        protected static function dispatch()
        {
            $function = 'FastRoute\simpleDispatcher';
            $params = array();

            $cacheDir = self::$applicationDirectory.'cache';
            $cacheFile = $cacheDir.'/fastroute';
            if (is_dir($cacheDir) && is_writable($cacheDir)) {

                if (!file_exists($cacheFile) || is_writable($cacheDir.'cache/fastroute')) {

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
            self::$router = $function(function(FastRoute\RouteCollector $r) use ($routes) {

                foreach ($routes as $routeObject) {

                    $r->addRoute($routeObject->allowedMethods, $routeObject->endpoint, $routeObject->fullname);

                }

            }, $params);

            $routeInfo = self::$router->dispatch(self::$request->method, self::$request->uri);
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

            $route = self::$routes[$routeInfo[1]];
            $route->uriData = $routeInfo[2];

            return $route;
        }

        /**
         * Fetches Core settings file.
         * @return bool
         */
        protected static function fetchSettings()
        {
            $settings = self::getConfigFile('settings');
            if (empty($settings)) {

                throw new SettingsNotFoundException();

            }

            self::$settings = $settings;

            return true;
        }

        /**
         * Returns path to application's DocumentRoot.
         * @return string
         */
        public static function getApplicationDirectory()
        {
            return self::$applicationDirectory;
        }

        /**
         * Searches for local configuration file, returning its content.
         * @param string $type - 'settings' or 'routes'.
         * @return array
         */
        protected static function getConfigFile(string $type): array
        {
            $localFilePath = self::$applicationDirectory."config/{$type}.php";
            if (!file_exists($localFilePath)) {

                return array();

            }

            $localSettings = require($localFilePath);
            return is_array($localSettings) ? $localSettings : [];
        }

        /**
         * Returns Monolog logger.
         * @return Monolog\Logger
         */
        public static function getLogger()
        {
            return self::$logger;
        }

        /**
         * Returns Core router.
         * @return FastRoute\Dispatcher
         */
        public static function getRouter()
        {
            return self::$router;
        }

        /**
         * Returns Core settings.
         * @return array
         */
        public static function getSettings(): array
        {
            return self::$settings;
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
                "routes" => null
            ];

            foreach (self::$settings['modules'] as $moduleName => $moduleConfig) {

                $moduleConfig = array_replace_recursive($default, $moduleConfig);

                $module = new ModuleMetadata();
                $module->name = $moduleName;
                $module->routes = $moduleConfig['routes'];
                $module->composerPath = $moduleConfig['path'];
                $module->composerNamespace = $moduleConfig['namespace'];

                if (!empty($module->composerNamespace) && !empty($module->composerPath)) {

                    self::$composerLoader->addPsr4($module->composerNamespace, self::$applicationDirectory.$module->composerPath);

                }

                if (!empty($module->routes) && file_exists(self::$applicationDirectory.$module->routes)) {

                    $moduleRoutes = require(self::$applicationDirectory.$module->routes);
                    if (is_array($moduleRoutes)) {

                        self::loadRoutes($module, $moduleRoutes);

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
    }
