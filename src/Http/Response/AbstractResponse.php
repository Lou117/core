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
    const HTTP_HEADER_ALLOW = "Allow";
    const HTTP_HEADER_CONTENT_TYPE = "Content-Type";
    const HTTP_HEADER_LOCATION = "Location";
    const HTTP_PROTOCOL = "HTTP/1.1";


    /**
     * Map of standard HTTP status code/reason phrases (from Guzzle).
     *
     * @var array
     */
    protected static $phrases = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => "I'm a teapot",
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    /**
     * @var int
     */
    protected $statusCode = 200;

    /**
     * @var string
     */
    protected $reasonPhrase = "";


    /**
     * @var mixed
     */
    protected $body;

    /**
     * @var array
     */
    protected $headers = [];


    public function __construct($body = null, int $status_code = 200)
    {
        $this->setStatus($status_code);
        if ($body !== null) {

            $this->setBody($body);

        }
    }

    /**
     * Adds an header to HTTP response.
     *
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
     * Returns HTTP response body as a string.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Returns HTTP response body charset.
     * Defaults to "utf-8".
     *
     * @return string
     */
    public function getCharset(): string
    {
        return "utf-8";
    }

    /**
     * Returns response MIME type.
     *
     * @return string
     */
    abstract public function getMimetype(): string;

    /**
     * Applies passed HTTP status code and body, and sends HTTP response to standard output (by calling header() and
     * echo()).
     * This method is automatically called by Core.
     *
     * @see Core::boot()
     */
    public function send()
    {
        if (!headers_sent()) {

            $this->sendHeaders();

        }

        echo $this->getBody();
    }

    /**
     * Sends HTTP response headers.
     *
     * @return AbstractResponse
     */
    protected function sendHeaders(): AbstractResponse
    {
        header(self::HTTP_PROTOCOL." {$this->statusCode} {$this->reasonPhrase}");
        if ($this->body !== null) {

            $mimeType = $this->getMimetype();
            if (empty($mimeType)) {

                throw new LogicException("Response body MIME type cannot be empty");

            }

            $charset = $this->getCharset();
            if (empty($charset)) {

                throw new LogicException("Response body charset cannot be empty");

            }

            $this->headers[self::HTTP_HEADER_CONTENT_TYPE] = "{$mimeType};charset={$charset}";

        }

        foreach ($this->headers as $header_name => $header_value) {

            header($header_name.': '.$header_value);

        }

        return $this;
    }

    /**
     * Sets HTTP response body.
     *
     * @param mixed $body - HTTP response body.
     * @return AbstractResponse
     */
    abstract public function setBody($body): AbstractResponse;

    /**
     * Sets HTTP response status code.
     * Response status code will be defaulting to "200 OK" if not overwritten using this method.
     *
     * @param int $status_code - HTTP status code.
     * @param string $reason_phrase - HTTP reason phrase.
     * @return AbstractResponse
     */
    public function setStatus(int $status_code, string $reason_phrase = ""): AbstractResponse
    {
        $this->statusCode = (int) $status_code;
        if ($reason_phrase == "" && isset(self::$phrases[$this->statusCode])) {

            $reason_phrase = self::$phrases[$this->statusCode];

        }

        $this->reasonPhrase = $reason_phrase;
        return $this;
    }
}
