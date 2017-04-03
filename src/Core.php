<?php
    namespace Lou117\Core;

    use \Exception;
    use \FastRoute;
    use Lou117\Core\Http\Request;
    use Lou117\Core\Http\Response;
    use Lou117\Core\Exchange\Problem;
    use Composer\Autoload\ClassLoader;
    use Lou117\Core\Exception\SettingsNotFoundException;

    class Core
    {
        /**
         * Application root directory path.
         * @var string
         */
        public static $applicationDirectory;

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
         * HTTP response.
         * @var Response
         */
        protected static $response;

        /**
         * Routing table.
         * @var [Route]
         */
        public static $routes;

        /**
         * API global settings.
         * @var array
         */
        public static $settings;


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
            self::$response = new Response();
            self::$composerLoader = $composer_loader;

            try {

                self::$applicationDirectory = $application_directory;
                if (substr(self::$applicationDirectory, -1, 1) !== '/') {

                    self::$applicationDirectory .= '/';

                }



                /* Settings processing */

                self::getSettings();
                if (array_key_exists('debugMode', self::$settings) && self::$settings['debugMode'] != false) {

                    Problem::$debugMode = true;

                }



                /* Modules loading */

                self::loadModules();



                /* Request processing */

                self::$request = new Request(true);

                $parsingResult = self::$request->parseRequestBody();
                if ($parsingResult === Request::PARSE_405) {

                    // Will die()
                    self::$response->send(Response::HTTP_405);

                }

                if ($parsingResult === Request::PARSE_415) {

                    // Will die()
                    self::$response->send(Response::HTTP_415);

                }



                /* Routing */

                $route = self::dispatch();

                /**/



                session_start();

                $moduleClass = $route->module->composerNamespace.'Module';
                new $moduleClass(self::$request, self::$response, $route);

            } catch (Exception $e) {

                // Will die()
                self::$response->send(Response::HTTP_500, new Problem($e));

            }
        }

        /**
         * Initializes and run FastRoute router. By default, Core will use FastRoute\simpleDispatcher function, but if
         * cache/ directory exists and is writable, FastRoute\cachedDispatcher will be preferred.
         * @return Route
         */
        protected static function dispatch():Route
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
            $dispatcher = $function(function(FastRoute\RouteCollector $r) use ($routes) {

                foreach ($routes as $routeObject) {

                    $r->addRoute($routeObject->allowedMethods, $routeObject->endpoint, $routeObject->fullname);

                }

            }, $params);

            $routeInfo = $dispatcher->dispatch(self::$request->method, self::$request->uri);
            if ($routeInfo[0] === FastRoute\Dispatcher::NOT_FOUND) {

                // Will die()
                self::$response->send(Response::HTTP_404);

            }

            if ($routeInfo[0] === FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {

                $allowedMethodsAsString = implode(', ', $routeInfo[1]);

                // Will die()
                self::$response->send(Response::HTTP_405, null, ["Allow: {$allowedMethodsAsString}"]);

            }

            $route = self::$routes[$routeInfo[1]];
            $route->uriData = $routeInfo[2];

            return $route;
        }

        /**
         * Retrieves and computes API settings file, storing found data on Core::$settings property.
         * @return bool
         */
        protected static function getSettings():bool
        {
            $settings = self::getConfigFile('settings');
            if (empty($settings)) {

                throw new SettingsNotFoundException();

            }

            self::$settings = $settings;

            return true;
        }

        /**
         * Searches for local configuration file, returning its content.
         * @param string $type - 'settings' or 'routes'.
         * @return array
         */
        protected static function getConfigFile(string $type):array
        {
            $localFilePath = self::$applicationDirectory."config/{$type}.php";
            if (!file_exists($localFilePath)) {

                return array();

            }

            $localSettings = require($localFilePath);
            return is_array($localSettings) ? $localSettings : [];
        }

        /**
         * Loads modules declared by settings file, adding them to Composer and loading their routes (if any).
         * @return bool
         */
        protected static function loadModules():bool
        {
            $default = [
                'composer' => [
                    'namespace' => null,
                    'path' => null
                ],
                'routes' => null
            ];

            foreach (self::$settings['modules'] as $moduleName => $moduleConfig) {

                $moduleConfig = array_replace_recursive($default, $moduleConfig);

                $module = new Module();
                $module->name = $moduleName;
                $module->routes = $moduleConfig['routes'];
                $module->composerPath = $moduleConfig['composer']['path'];
                $module->composerNamespace = $moduleConfig['composer']['namespace'];

                if (!empty($module->composerNamespace) && !empty($module->composerPath)) {

                    self::$composerLoader->addPsr4($module->composerNamespace, $module->composerPath);

                }

                if (!empty($module->routes) && file_exists($module->routes)) {

                    $moduleRoutes = require($module->routes);
                    if (is_array($moduleRoutes)) {

                        self::loadRoutes($module, $moduleRoutes);

                    }

                }

            }

            return true;
        }

        /**
         * Loads module routes, adding them to internal routing table.
         * @param Module $module - Module configuration.
         * @param array $routes - Module routes.
         * @return bool
         */
        protected static function loadRoutes(Module $module, array $routes) {

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
                $route->fullname = $module->name.$route->name;
                $route->allowedMethods = $routeConfig['allowedMethods'];

                self::$routes[$route->fullname] = $route;

            }

            return true;

        }
    }
