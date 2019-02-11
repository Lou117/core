<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 2019-02-08
 * Time: 17:48
 */
namespace Lou117\Core;

interface RoutingTableParserInterface
{
    /**
     * Parses given $routing_table, and returns an indexed array of Route instances.
     * @param mixed $routing_table
     * @return Route[]
     */
    static public function parse($routing_table): array;
}
