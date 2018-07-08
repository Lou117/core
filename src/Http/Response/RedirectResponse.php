<?php
/**
 * Created by PhpStorm.
 * User: Sylvain Glaçon
 * Date: 29/06/2017
 * Time: 16:11
 */
namespace Lou117\Core\Http\Response;

use \InvalidArgumentException;

class RedirectResponse extends EmptyResponse
{
    /**
     * @var int
     */
    protected $statusCode = 302;


    public function __construct($body = null, int $status_code = 302)
    {
        $body = (string) trim($body);
        if (empty($body)) {

            throw new InvalidArgumentException("RedirectResponse body is Location header value and cannot be empty");

        }

        parent::__construct($body, $status_code);
        $this->addHeader(AbstractResponse::HTTP_HEADER_LOCATION, $body);
    }
}
