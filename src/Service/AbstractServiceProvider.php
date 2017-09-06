<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 05/07/2017
 * Time: 15:57
 */
namespace Lou117\Core\Service;

abstract class AbstractServiceProvider
{
    public function __construct(array $services) {}

    abstract public function get();
}
