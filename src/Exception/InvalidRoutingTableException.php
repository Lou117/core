<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 16/10/2017
 * Time: 10:54
 */

namespace Lou117\Core\Exception;

use \Exception;

class InvalidRoutingTableException extends Exception
{
    /**
     * @see Exception::$message
     */
    protected $message = "Routing table file must return an PHP array";
}
