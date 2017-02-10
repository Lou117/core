<?php
    namespace Lou117\Core\Http;

    use Lou117\Core\Exchange\Hal\HalResource;
    use Lou117\Core\Exchange\Problem;

    class Response
    {
        const HTTP_PROTOCOL = 'HTTP/1.1';
        const HTTP_200 = '200 OK';
        const HTTP_301 = '301 Moved Permanently';
        const HTTP_302 = '302 Moved Temporarily';
        const HTTP_303 = '303 See Other';
        const HTTP_400 = '400 Bad Request';
        const HTTP_401 = '401 Unauthorized';
        const HTTP_403 = '403 Forbidden';
        const HTTP_404 = '404 Not Found';
        const HTTP_405 = '405 Method Not Allowed';
        const HTTP_500 = '500 Internal Server Error';
        const HTTP_503 = '503 Service Unavailable';
        const HTTP_406 = '406 Not Acceptable';
        const HTTP_415 = '415 Unsupported Media Type';
        const HTTP_422 = '422 Unprocessable Entity';


        public $body;

        public $headers = array();

        public $statusCode = self::HTTP_200;


        public function setBody($body):Response
        {
            $this->body = $body;
            return $this;
        }

        public function setStatusCode(string $status_code):Response
        {
            $this->statusCode = $status_code;
            return $this;
        }

        /**
         * Send response status code, headers and body, and die().
         * If headers has already been sent, this method will store $body in Response::body property and die() will
         * receive Response::body as parameter. Otherwise, Response::$body will be casted and Content-Type header will
         * be set accordingly :
         * - if Response::$body is casted as HalResource instance, Content-Type will be set to 'application/hal+json' ;
         * - if Response::$body is casted as Problem instance, Content-Type will be set to 'application/problem+json',
         * with Problem::$status and Problem::$title properties set accordingly with Response::$statusCode.
         * Any other body type will be encoded to JSON (w
         * @param string|null $status_code
         * @param null $body
         * @param array|null $headers
         */
        public function send(string $status_code = null, $body = null, array $headers = null)
        {
            if ($body !== null) {

                $this->setBody($body);

            }

            if (headers_sent()) {

                die($this->body);

            }

            if (!empty($status_code)) {

                $this->statusCode = $status_code;

            }

            if (!empty($headers)) {

                $this->headers = array_replace($this->headers, $headers);

            }

            $message = self::HTTP_PROTOCOL . ' ' . $this->statusCode;
            $this->headers[] = $message;

            $contentType = 'application/json';

            if ($this->body instanceof HalResource) {

                $contentType = 'application/hal+json';

            }

            if ($this->body instanceof Problem) {

                $this->body->status = $this->statusCode;
                $this->body->title = substr($this->statusCode, 4);

                $contentType = 'application/problem+json';

            }

            $this->headers[] = 'Content-type: ' . $contentType;
            $this->sendHeaders();

            if (!empty($this->body)) {

                $try = json_encode($this->body);
                die(!empty($try) ? $try : '');

            } else {

                die();

            }
        }

        /**
         * Send response headers.
         * @return Response
         */
        protected function sendHeaders():Response
        {
            foreach ($this->headers as $header) {

                header($header);

            }

            return $this;
        }
    }
