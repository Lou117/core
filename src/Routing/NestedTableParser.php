<?php declare(strict_types=1);
namespace Lou117\Core\Routing;

use LogicException;

class NestedTableParser extends AbstractTableParser
{
    /**
     * Includes PHP file located at given `$routing_table_file_path`, parses it as a Core v3.1+ nested routing table,
     * and returns an array of `Route` instances.
     *
     * File existence is checked by `Core::loadRoutingTableFile()` method.
     * @param string $routing_table_file_path - routing table file path.
     * @return Route[]
     */
    public function parse(string $routing_table_file_path): array
    {
        $fileRoutes = require($routing_table_file_path);

        if (is_array($fileRoutes) === false) {
            throw new LogicException("Legacy routing table file must be a PHP script returning an array");
        }

        $return = [];

        foreach ($fileRoutes as $endpoint => $routing_table_entry) {
            $return = array_merge($return, $this->parseEntry($endpoint, $routing_table_entry));
        }

        return $return;
    }

    /**
     * Recursively parse given `$routing_table_entry`, using given `$endpoint` and overriding given
     * `$inherited_arguments` and `$inherited_attributes`, if any.
     * @param string $endpoint - route endpoint.
     * @param array $routing_table_entry - routing table entry.
     * @param array $inherited_arguments (optional, defaults to an empty array) - arguments inherited from all parent
     * route entries, if any.
     * @param array $inherited_attributes (optional, defaults to an empty array) - attributes inherited from all parent
     * route entries, if any.
     * @return Route[]
     */
    protected function parseEntry(string $endpoint, array $routing_table_entry, array $inherited_arguments = [], array $inherited_attributes = []): array
    {
        $return = [];

        if (!str_starts_with($endpoint, "/")) {
            $endpoint = "/{$endpoint}";
        }

        if (str_ends_with($endpoint, "/")) {
            $endpoint = substr($endpoint, 0, strlen($endpoint) - 1);
        }

        $routing_table_entry = array_replace_recursive([
            "methods"       => [],
            "children"      => [],
            "arguments"     => $inherited_arguments,
            "controller"    => null
        ], $routing_table_entry);

        $routing_table_entry["methods"] = array_map(function($method){
            return strtoupper($method);
        }, array_filter($routing_table_entry["methods"], function($method){
            return is_string($method);
        }));

        $attributes = array_replace_recursive(
            $inherited_attributes,
            array_filter($routing_table_entry, function($key){
                return !in_array($key, ["methods", "children", "arguments", "controller"]);
            }, ARRAY_FILTER_USE_KEY)
        );

        foreach ($routing_table_entry["children"] as $child_endpoint => $child_entry) {
            $return = array_merge($return, self::parseEntry(
                $endpoint.$child_endpoint,
                $child_entry,
                $routing_table_entry["arguments"],
                $attributes
            ));
        }

        $route = new Route();
        $route->endpoint = $endpoint;
        $route->arguments = $routing_table_entry["arguments"];
        $route->attributes = $attributes;

        if (is_array($routing_table_entry["controller"])) {
            foreach ($routing_table_entry["controller"] as $method => $controller) {
                if (in_array($method, $routing_table_entry["methods"])) {
                    $clonedRoute = clone $route;
                    $clonedRoute->methods = [$method];
                    $clonedRoute->controller = $controller;
                    $return[] = $clonedRoute;
                }
            }
        } elseif (trim($routing_table_entry["controller"]) !== "") {
            $route->methods = $routing_table_entry["methods"];
            $route->controller = $routing_table_entry["controller"];
            $return[] = $route;
        }

        return $return;
    }
}
