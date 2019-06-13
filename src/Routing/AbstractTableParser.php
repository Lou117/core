<?php
namespace Lou117\Core\Routing;

use Psr\Log\LoggerInterface;

abstract class AbstractTableParser
{
    /**
     * @var LoggerInterface
     */
    protected $logger;


    /**
     * @param LoggerInterface $logger - logger instance to use if routing table parsing generates log messages.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Parses file located at given $routing_table_file_path, and returns an array of Route instances.
     *
     * When this method is called, file existence has already been checked by Core::loadRoutingTableFile() method.
     *
     * @param string $routing_table_file_path - routing table file path.
     * @return Route[]
     */
    abstract public function parse(string $routing_table_file_path): array;
}
