<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 23/07/2018
 * Time: 19:41
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TestMiddlewareBar implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        return $response->withHeader("X-MiddlewareBar-Test", time());
    }
}
