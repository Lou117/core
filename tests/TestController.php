<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 23/07/2018
 * Time: 19:21
 */

use GuzzleHttp\Psr7\Response;
use \Lou117\Core\AbstractController;
use Psr\Http\Message\ResponseInterface;

class TestController extends AbstractController
{
    public function foo(): ResponseInterface
    {
        $response = new Response();
        return $response->withHeader("X-Controller-Test", time());
    }

    public function baz()
    {
        return null;
    }
}
