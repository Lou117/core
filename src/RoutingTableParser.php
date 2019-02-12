<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 2019-02-08
 * Time: 17:47
 */
namespace Lou117\Core;

use \LogicException;

class RoutingTableParser extends AbstractRoutingTableParser
{
    /**
     * @throws LogicException - If routing table file does not return a PHP array.
     */
    public function parse(string $routing_table_path): array
    {
        $return = [];

        $routing_table = require($routing_table_path);
        if (!is_array($routing_table)) {
            throw new LogicException("Invalid routing table file: must return a PHP array");
        }

        foreach ($routing_table as $endpoint => $routing_table_entry) {
            $return = array_merge($return, self::parseEntry($endpoint, $routing_table_entry));
        }

        return $return;
    }

    /**
     * Recursively parse given $routing_table_entry, using given $endpoint and overriding given $inherited_arguments and
     * $inherited_attributes, if any.
     * @param string $endpoint - Route endpoint.
     * @param array $routing_table_entry - Routing table entry.
     * @param array $inherited_arguments (optional, defaults to an empty array) - Arguments inherited from all parent route entries, if any.
     * @param array $inherited_attributes (optional, defaults to an empty array) - Attributes inherited from all parent route entries, if any.
     * @return Route[]
     */
    protected function parseEntry(string $endpoint, array $routing_table_entry, array $inherited_arguments = [], array $inherited_attributes = []): array
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

        $routing_table_entry["methods"] = array_map(function($method){
            return strtoupper($method);
        }, array_filter($routing_table_entry["methods"], function($method){
            return is_string($method);
        }));

        if (empty($routing_table_entry["methods"])) {
            $this->coreLogger->addWarning("Endpoint {$endpoint} has no method allowed, skipped");
            return [];
        }

        $attributes = array_replace_recursive(
            $inherited_attributes,
            array_filter($routing_table_entry, function($key){
                return !in_array($key, ["methods", "children", "arguments", "controller"]);
            }, ARRAY_FILTER_USE_KEY)
        );

        foreach ($routing_table_entry["children"] as $child_endpoint => $child_entry) {
            $return += self::parseEntry($endpoint.$child_endpoint, $child_entry, $routing_table_entry["arguments"], $attributes);
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
