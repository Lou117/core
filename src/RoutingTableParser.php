<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 2019-02-08
 * Time: 17:47
 */
namespace Lou117\Core;

class RoutingTableParser implements RoutingTableParserInterface
{
    /**
     * Parses given $routing_table using default behavior of Core framework. Expects an associative array.
     * @param array $routing_table
     * @return Route[]
     */
    static public function parse($routing_table): array
    {
        $return = [];

        foreach ($routing_table as $endpoint => $routing_table_entry) {
            $return += self::parseEntry($endpoint, $routing_table_entry, [], []);
        }

        return $return;
    }

    /**
     * @param string $endpoint
     * @param array $routing_table_entry
     * @param array $inherited_arguments
     * @param array $inherited_attributes
     * @return Route[]
     */
    static protected function parseEntry(string $endpoint, array $routing_table_entry, array $inherited_arguments, array $inherited_attributes): array
    {
        $return = [];

        if (substr($endpoint, 0, 1) !== "/") {
            $endpoint = "/{$endpoint}";
        }

        if (substr($endpoint, -1) === "/") {
            $endpoint = substr($endpoint, 0, strlen($endpoint) - 1);
        }

        $routing_table_entry = array_replace_recursive([
            "methods" => [],
            "children" => [],
            "arguments" => $inherited_arguments,
            "controller" => null
        ], $routing_table_entry);

        $attributes = array_replace_recursive(
            $inherited_attributes,
            array_filter($routing_table_entry, function($key){
                return !in_array($key, ["methods", "children", "arguments", "controller"]);
            }, ARRAY_FILTER_USE_KEY)
        );

        foreach ($routing_table_entry["children"] as $child_endpoint => $child_entry) {
            $return += self::parseEntry($endpoint.$child_endpoint, $child_entry, $routing_table_entry["arguments"], $attributes);
        }

        if (is_array($routing_table_entry["controller"])) {
            foreach ($routing_table_entry["controller"] as $method => $controller) {
                if (in_array($method, $routing_table_entry["methods"])) {
                    $route = new Route();
                    $route->endpoint = $endpoint;
                    $route->methods = [$method];
                    $route->controller = $controller;
                    $route->arguments = $routing_table_entry["arguments"];
                    $route->attributes = $attributes;
                    $return[] = $route;
                }
            }
        } elseif (trim($routing_table_entry["controller"]) !== "") {
            $route = new Route();
            $route->endpoint = $endpoint;
            $route->methods = $routing_table_entry["methods"];
            $route->controller = $routing_table_entry["controller"];
            $route->arguments = $routing_table_entry["arguments"];
            $route->attributes = $attributes;
            $return[] = $route;
        }

        return $return;
    }
}