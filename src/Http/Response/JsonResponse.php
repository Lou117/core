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
     * @see AbstractResponse::getBody()
     * @return string
     */
    public function getBody(): string
    {
        if (is_array($this->body) || is_object($this->body)) {

            $return = json_encode($this->body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($return === null) {

                $error = json_last_error_msg();
                throw new RuntimeException("JSON encoding has failed ({$error})");

            }

            return $return;

        }

        return "";
    }

    /**
     * @see AbstractResponse::getMimetype()
     * @return string
     */
    public function getMimetype(): string
    {
        return "application/json";
    }

    /**
     * Sets HTTP response body.
     *
     * @param array|object $body
     * @param bool $merge - If set to true, and if passed body and existing body are both of type array, passed body
     * will be merged with existing body using array_replace_recursive.
     * @return AbstractResponse
     */
    public function setBody($body, bool $merge = false): AbstractResponse
    {
        if (!is_array($body) && !is_object($body)) {

            $type = gettype($body);
            throw new InvalidArgumentException("JsonResponse::setBody() expects parameter 1 to be array or object, {$type} given");

        }

        if (is_array($body) && is_array($this->body) && $merge) {

            $this->body = array_replace_recursive($this->body, $body);

        } else {

            $this->body = $body;

        }

        return $this;
    }
}
