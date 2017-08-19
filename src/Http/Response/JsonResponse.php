<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 29/06/2017
 * Time: 16:11
 */
namespace Lou117\Core\Http\Response;

use \RuntimeException;
use \InvalidArgumentException;

class JsonResponse extends AbstractResponse
{
    /**
     * @see AbstractResponse::$contentMimeType
     */
    protected $contentMimeType = 'application/json';

    /**
     * Sets HTTP response body, forcing passed value to string type using settype().
     * @see AbstractResponse::setBody()
     * @see settype()
     * @param mixed $body - HTTP response body.
     * @return AbstractResponse
     * @throws InvalidArgumentException - when passed value type is not array nor object
     * @throws RuntimeException - when JSON encoding failed
     */
    public function setBody($body): AbstractResponse
    {
        if (!is_array($body) && !is_object($body)) {

            $type = gettype($body);
            throw new InvalidArgumentException("JsonResponse::setBody() expects parameter 1 to be array or object, {$type} given");

        }

        $this->body = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($body === null) {

            $error = json_last_error_msg();
            throw new RuntimeException("JSON encoding has failed ({$error})");

        }

        return $this;
    }
}
