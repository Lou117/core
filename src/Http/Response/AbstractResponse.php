<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 29/06/2017
 * Time: 14:20
 */
namespace Lou117\Core\Http\Response;

use \LogicException;

abstract class AbstractResponse
{
    const HTTP_PROTOCOL = 'HTTP/1.1';

    const HTTP_200 = '200 OK';
    const HTTP_201 = '201 Created';
    const HTTP_204 = '204 No Content';
    const HTTP_301 = '301 Moved Permanently';
    const HTTP_302 = '302 Moved Temporarily';
    const HTTP_303 = '303 See Other';
    const HTTP_400 = '400 Bad Request';
    const HTTP_401 = '401 Unauthorized';
    const HTTP_403 = '403 Forbidden';
    const HTTP_404 = '404 Not Found';
    const HTTP_405 = '405 Method Not Allowed';
    const HTTP_409 = '409 Conflict';
    const HTTP_500 = '500 Internal Server Error';
    const HTTP_501 = '501 Not Implemented';
    const HTTP_503 = '503 Service Unavailable';
    const HTTP_406 = '406 Not Acceptable';
    const HTTP_415 = '415 Unsupported Media Type';
    const HTTP_422 = '422 Unprocessable Entity';

    const HTTP_HEADER_ALLOW = 'Allow';
    const HTTP_HEADER_CONTENT_TYPE = 'Content-Type';


    /**
     * @var mixed
     */
    protected $body;

    /**
     * @var string
     */
    protected $contentMimeType;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var string
     */
    protected $statusCode = self::HTTP_200;


    public function __construct($body = null)
    {
        if (!empty($body)) {

            $this->setBody($body);

        }
    }

    /**
     * Adds an header to HTTP response.
     * @param string $header_name - name of HTTP header to add to HTTP response.
     * @param string $header_value - value for HTTP header.
     * @return AbstractResponse
     */
    public function addHeader(string $header_name, string $header_value): AbstractResponse
    {
        $this->headers[$header_name] = $header_value;
        return $this;
    }

    /**
     * Applies passed HTTP status code and body, and sends HTTP response to standard output (by calling header() and
     * echo()). This method is automatically called by Core::boot() method.
     * @see Core::boot()
     * @see AbstractResponse::setStatusCode()
     * @see AbstractResponse::setBody()
     * @param string|null $status_code - optional, see AbstractResponse::setStatusCode()
     * @param null $body - optional, see AbstractResponse::setBody()
     * @return AbstractResponse
     */
    public function send(string $status_code = null, $body = null): AbstractResponse
    {
        if (!empty($status_code)) {

            $this->setStatusCode($status_code);

        }

        if ($body !== null) {

            $this->setBody($body);

        }

        if (headers_sent()) {

            echo $this->body;

        }

        $this->sendHeaders();

        echo $this->body;

        return $this;
    }

    /**
     * Sends HTTP response headers.
     * @return AbstractResponse
     */
    protected function sendHeaders(): AbstractResponse
    {
        if (empty($this->contentMimeType)) {

            throw new LogicException('Response body MIME type must be set by overriding AbstractResponse::contentMimeType property');

        }

        $this->headers[self::HTTP_HEADER_CONTENT_TYPE] = $this->contentMimeType;

        header(self::HTTP_PROTOCOL . ' ' . $this->statusCode);
        foreach ($this->headers as $header_name => $header_value) {

            header($header_name.': '.$header_value);

        }

        return $this;
    }

    /**
     * Sets HTTP response body. This method is responsible for preventing any incompatibility between passed value and
     * final body format (for ex. passing a string for a JSON formatted response), by throwing an exception (e.g.
     * LogicException) or implementing (and documenting) some fallback behavior.
     * @param mixed $body - HTTP response body.
     * @return AbstractResponse
     */
    abstract public function setBody($body): AbstractResponse;

    /**
     * Sets HTTP response status code. Response status code will be defaulting to "200 OK" if not overwritten using this
     * method.
     * @param string $status_code - AbstractResponse class exposes some of the most common status code via
     * AbstractResponse::HTTP_* constants, which can be use as parameter for this method. If you're not using one of
     * AbstractResponse::HTTP_* constants, ensure the value you pass starts with HTTP code followed by message
     * ("206 Partial Content").
     * @return AbstractResponse
     */
    public function setStatusCode(string $status_code): AbstractResponse
    {
        $this->statusCode = $status_code;
        return $this;
    }
}
