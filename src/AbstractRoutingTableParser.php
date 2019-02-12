<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 2019-02-08
 * Time: 17:48
 */
namespace Lou117\Core;

use Monolog\Logger;

abstract class AbstractRoutingTableParser
{
    /**
     * @var Logger
     */
    protected $coreLogger;


    /**
     * @param Logger $core_logger
     */
    public function setLogger(Logger $core_logger)
    {
        $this->coreLogger = $core_logger;
    }

    /**
     * Parses given $routing_table, and returns an indexed array of Route instances.
     * @param string $routing_table_path - Path to routing table file. This path has been validated (for existence only)
     * by Core::loadRoutingTable() method.
     * @return Route[]
     */
    abstract public function parse(string $routing_table_path): array;
}
