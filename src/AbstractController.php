<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 01/11/2017
 * Time: 17:06
 */

namespace Lou117\Core;

use Lou117\Core\Http\Response\AbstractResponse;

abstract class AbstractController
{
    public function __construct(Core $core_instance)
    {

    }

    /**
     * AbstractController method called by Core.
     *
     * @param $controller_method
     * @return AbstractResponse
     */
    public function run($controller_method): AbstractResponse
    {
        return $this->{$controller_method}();
    }
}
