<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 29/06/2017
 * Time: 16:11
 */
namespace Lou117\Core\Http\Response;

class HtmlResponse extends TextResponse
{
    /**
     * @see AbstractResponse::getMimetype()
     * @return string
     */
    public function getMimetype(): string
    {
        return "text/html";
    }
}
