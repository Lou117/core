<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 2019-02-08
 * Time: 17:47
 */
namespace Lou117\Core\Routing;

use \LogicException;

class LegacyTableParser extends AbstractTableParser
{
    /**
     * Includes PHP file located at given $file_path, parses its content as a Core v3.0.* routing table, and returns an
     * array of Route instances.
     *
     * File existence is checked by Core::loadRoutingTableFile() method.
     *
     * @param string $routing_table_file_path
     * @return Route[]
     * @throws LogicException - when file located at given $file_path is not a PHP script returning an array.
     */
    public function parse(string $routing_table_file_path): array
    {
        $fileRoutes = require($routing_table_file_path);

        if (is_array($fileRoutes) === false) {
            throw new LogicException("Legacy routing table file must be a PHP script returning an array");
        }

        $defaultRouteConfiguration = [
            "methods" => [],
            "endpoint" => null,
            "arguments" => [],
            "controller" => null
        ];

        $routes = [];

        foreach($fileRoutes as $routeName => $fileRoute) {
            $fileRoute = array_replace_recursive($defaultRouteConfiguration, $fileRoute);

            if (empty($fileRoute["methods"])) {
                $this->logger->warning("Route <{$routeName}> has no method allowed and was ignored");
                continue;
            }

            if (
                is_null($fileRoute["endpoint"])
                || trim($fileRoute["endpoint"]) === ""
            ) {
                $this->logger->warning("Route <{$routeName}> has an empty pattern and was ignored");
                continue;
            }

            $route = new Route();
            $route->name = $routeName;
            $route->methods = $fileRoute["methods"];
            $route->endpoint = $fileRoute["endpoint"];
            $route->arguments = $fileRoute["arguments"];
            $route->controller = $fileRoute["controller"];

            unset(
                $fileRoute["endpoint"],
                $fileRoute["methods"],
                $fileRoute["arguments"],
                $fileRoute["controller"]
            );

            $route->attributes = $fileRoute;
            $routes[] = $route;
        }

        return $routes;
    }
}
