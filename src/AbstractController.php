<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 01/11/2017
 * Time: 17:06
 */

namespace Lou117\Core;

use \Exception;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractController
{
    public function __construct(Core $core_instance)
    {

    }

    /**
     * AbstractController method called by Core.
     *
     * @param $controller_method
     * @return ResponseInterface
     * @throws Exception
     */
    public function run($controller_method): ResponseInterface
    {
        if (method_exists($this, $controller_method) === false) {

            $class = get_class($this);
            throw new Exception("Method {$controller_method} declared in routes doesn't exists in {$class}");

        } else {

            return $this->{$controller_method}();

        }
    }
}
