<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 2019-02-08
 * Time: 17:47
 */
namespace Lou117\Core;

use \LogicException;

class LegacyRoutingTableParser extends AbstractRoutingTableParser
{
    /**
     * @inheritdoc
     * @throws LogicException - If routing table file does not return a PHP array.
     */
    public function parse(string $routing_table_path): array
    {
        $loadedRoutes = require($routing_table_path);

        if (!is_array($loadedRoutes)) {
            throw new LogicException("Invalid routing table file: must return a PHP array");
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

            if (empty($routeConfig["methods"])) {
                $this->coreLogger->warning("Route '{$routeName}' has no method allowed and is ignored");
                continue;
            }

            if (is_null($routeConfig["endpoint"]) || trim($routeConfig["endpoint"]) === "") {
                $this->coreLogger->warning("Route '{$routeName}' has an empty pattern and is ignored");
                continue;
            }

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

        return $routes;
    }
}
