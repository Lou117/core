<?php
    namespace Lou117\Core;

    use Lou117\Core\Exception\SettingsNotFoundException;
    use Lou117\Core\Exception\RoutesNotFoundException;
    use Lou117\Core\Exchange\Problem;
    use Lou117\Core\Http\Request;
    use Lou117\Core\Http\Response;
    use \Exception;
    use \FastRoute;

    class Core
    {
        /**
         * Application root directory path.
         * @var string
         */
        public static $applicationDirectory;

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
         * @param string $application_directory - Application root directory path, to be used for retrieval of all
         * needed files (mainly routes and settings) and FastRoute cache writing.
         */
        public static function boot(string $application_directory)
        {
            self::$response = new Response();

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

                self::getRoutes();
                $route = self::dispatch();

                session_start();

                $moduleClass = $route->moduleNamespace.'\Module';
                new $moduleClass(self::$request, self::$response, $route);

            } catch (Exception $e) {

                // Will die()
                self::$response->send(Response::HTTP_503, new Problem($e));

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

                    $r->addRoute($routeObject->allowedMethods, $routeObject->endpoint, $routeObject->name);

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
         * Retrieves and computes routes configuration file, storing found routes on Core::$routes property as an
         * associative array where keys are route identifiers and values are Lou117\Core\Route instances.
         * @return bool
         */
        protected static function getRoutes():bool
        {
            $routes = self::getConfigFile('routes');
            if (empty($routes)) {

                throw new RoutesNotFoundException();

            }

            foreach ($routes as $routeName => $routeConfig) {

                $route = new Route();
                $route->name = $routeName;
                $route->moduleNamespace = $routeConfig['moduleNamespace'];
                $route->endpoint = $routeConfig['endpoint'];
                $route->allowedMethods = $routeConfig['allowedMethods'];

                self::$routes[$route->name] = $route;

            }

            return true;
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
         * Gathers default and local configuration files, returning final merged configuration.
         * @param string $type - 'settings' or 'routes'.
         * @return array
         */
        protected static function getConfigFile(string $type):array
        {
            $localFilePath = self::$applicationDirectory."config/{$type}.php";
            $defaultFilePath = self::$applicationDirectory."config/default/{$type}.php";
            if (!file_exists($localFilePath) && !file_exists($defaultFilePath)) {

                return array();

            }

            $config = [
                'local' => file_exists($localFilePath) ? require $localFilePath : array(),
                'default' => require $defaultFilePath
            ];

            foreach ($config as $type => $content) {

                if (!is_array($content)) {

                    $config[$type] = array();

                }

            }

            return array_replace_recursive($config['default'], $config['local']);
        }
    }
