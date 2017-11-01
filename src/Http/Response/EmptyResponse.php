<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 01/11/2017
 * Time: 13:03
 */

namespace Lou117\Core\Http\Response;

/**
 * Class EmptyResponse
 * @package Core\Http\Response
 */
class EmptyResponse extends AbstractResponse
{
    /**
     * Forcing response body to an empty value.
     *
     * @var null
     */
    protected $body = "";


    /**
     * Returns an empty string, as HTTP responses with empty body must not have any Content-Type header.
     *
     * @return string
     */
    public function getCharset(): string
    {
        return "";
    }

    /**
     * Returns an empty string, as HTTP responses with empty bodies must not have any Content-Type header.
     *
     * @return string
     */
    public function getMimetype(): string
    {
        return "";
    }

    /**
     * Forcing response body to an empty value.
     *
     * @param mixed $body
     * @return AbstractResponse
     */
    public function setBody($body): AbstractResponse
    {
        return $this;
    }
}
