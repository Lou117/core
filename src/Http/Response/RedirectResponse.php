<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 29/06/2017
 * Time: 16:11
 */
namespace Lou117\Core\Http\Response;

class RedirectResponse extends AbstractResponse
{
    /**
     * @see AbstractResponse::$contentMimeType
     */
    protected $contentMimeType = 'text/plain';


    public function __construct($body = null)
    {
        $this->setStatusCode(AbstractResponse::HTTP_302);
        $this->addHeader('Location', $body);
    }

    /**
     * @see AbstractResponse::setBody()
     * @param mixed $body - HTTP response body.
     * @return AbstractResponse
     */
    public function setBody($body): AbstractResponse
    {
        return $this;
    }
}
