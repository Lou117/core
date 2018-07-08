<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 01/11/2017
 * Time: 17:06
 */

namespace Lou117\Core;

use \Exception;
use \LogicException;
use Lou117\Core\Container\Container;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractController
{
    public function __construct(Container $core_container)
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

            $response = $this->{$controller_method}();
            if (($response instanceof ResponseInterface) === false) {

                throw new LogicException("Method {$controller_method} must return an instance of PSR-7 ResponseInterface");

            }

            return $this->{$controller_method}();

        }
    }
}
