<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 29/06/2017
 * Time: 16:11
 */
namespace Lou117\Core\Http\Response;

use \RuntimeException;
use Lou117\Core\Problem;
use \InvalidArgumentException;

/**
 * HTTP response handler for "problem details" responses. Relies on Lou117/core package.
 * @see https://tools.ietf.org/html/draft-nottingham-http-problem-06
 */
class ProblemResponse extends AbstractResponse
{
    /**
     * @see AbstractResponse::$contentMimeType
     */
    protected $contentMimeType = 'application/json';

    /**
     * Sets HTTP response body.
     * @see AbstractResponse::setBody()
     * @param Problem $body - HTTP response body.
     * @return AbstractResponse
     * @throws InvalidArgumentException - when passed value is not an instance of Problem
     * @throws RuntimeException - when JSON encoding failed
     */
    public function setBody($body): AbstractResponse
    {
        if (!($body instanceof Problem)) {

            $type = gettype($body);
            $type = $type == 'object' ? get_class($body) : $type;
            throw new InvalidArgumentException("ProblemResponse::setBody() expects parameter 1 to be instance of Problem, {$type} given");

        }

        $body->status = $this->statusCode;
        $body->title = substr($this->statusCode, 4);

        $this->encode($body);

        return $this;
    }

    /**
     * @see AbstractResponse::setStatusCode()
     * Propagates new status code to embedded body, if set.
     */
    public function setStatusCode(string $status_code): AbstractResponse
    {
        parent::setStatusCode($status_code);
        if (!empty($this->body)) {

            $body = json_decode($this->body, true);
            $body['status'] = $this->statusCode;
            $body['title'] = substr($this->statusCode, 4);
            $this->encode($body);

        }

        return $this;
    }

    /**
     * Encode given body to JSON.
     * @param $new_body
     * @return ProblemResponse
     */
    protected function encode($new_body): ProblemResponse
    {
        $this->body = json_encode($new_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($this->body === null) {

            $error = json_last_error_msg();
            throw new RuntimeException("JSON encoding has failed ({$error})");

        }

        return $this;
    }
}
