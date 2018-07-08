<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 01/11/2017
 * Time: 17:06
 */

namespace Lou117\Core;

use \Exception;
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
     * @throws Exception
     */
    public function run($controller_method): AbstractResponse
    {
        if (method_exists($this, $controller_method) === false) {

            $class = get_class($this);
            throw new Exception("Method {$controller_method} declared in routes doesn't exists in {$class}");

        } else {

            return $this->{$controller_method}();

        }
    }
}
