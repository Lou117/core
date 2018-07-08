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
 * HTTP response handler for "problem details" responses.
 * @see https://tools.ietf.org/html/draft-nottingham-http-problem-06
 */
class ProblemResponse extends AbstractResponse
{
    /**
     * @return string
     */
    public function getMimetype(): string
    {
        return "application/problem+json";
    }

    /**
     * Sets given Problem instance as HTTP response body, hydrating it with HTTP response status code and reason phrase
     * before encoding it to JSON.
     *
     * @param Problem $body - HTTP response body.
     * @return AbstractResponse
     * @throws InvalidArgumentException - When passed value is not an instance of Problem.
     * @throws RuntimeException - When JSON encoding failed.
     */
    public function setBody($body): AbstractResponse
    {
        if (!($body instanceof Problem)) {

            $type = gettype($body);
            $type = $type == 'object' ? get_class($body) : $type;
            throw new InvalidArgumentException("ProblemResponse::setBody() expects parameter 1 to be instance of Problem, {$type} given");

        }

        $body->status = $this->statusCode;
        $body->title = $this->reasonPhrase;

        $this->body = $body;
        return $this;
    }

    /**
     * Sets HTTP response status code and propagates new status code to embedded body, if set.
     *
     * @param int $status_code
     * @param string $reason_phrase
     * @return AbstractResponse
     */
    public function setStatus(int $status_code, string $reason_phrase = ""): AbstractResponse
    {
        parent::setStatus($status_code, $reason_phrase);
        if ($this->body instanceof Problem) {

            $this->body->status = $this->statusCode;
            $this->body->title = $this->reasonPhrase;

        }

        return $this;
    }

    /**
     * Encodes HTTP response body to JSON.
     *
     * @return string
     */
    public function getBody(): string
    {
        $return = json_encode($this->body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($return === null) {

            $error = json_last_error_msg();
            throw new RuntimeException("JSON encoding has failed ({$error})");

        }

        return $return;
    }
}
