<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 29/06/2017
 * Time: 16:11
 */
namespace Lou117\Core\Http\Response;

class TextResponse extends AbstractResponse
{
    /**
     * @see AbstractResponse::$contentMimeType
     */
    protected $contentMimeType = 'text/plain';

    /**
     * Sets HTTP response body, forcing passed value to string type using settype().
     * @see AbstractResponse::setBody()
     * @see settype()
     * @param mixed $body - HTTP response body.
     * @return AbstractResponse
     */
    public function setBody($body): AbstractResponse
    {
        $this->body = $body;
        settype($this->body, 'string');
        return $this;
    }
}
