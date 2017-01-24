<?php
    namespace Lou117\Core\Http;

    class Request
    {
        /**
         * Returned by Request::parseRequestBody method when parsing was successful.
         * @see Request::parseRequestBody()
         */
        const PARSE_200 = 200;

        /**
         * Returned by Request::parseRequestBody method when request body
         */
        const PARSE_405 = 405;
        const PARSE_415 = 415;

        /**
         * Request body
         * @var mixed
         */
        public $body;

        /**
         * Files
         * @var array
         */
        public $files;

        /**
         * Request headers, as an associative array where keys are header name and values are header value. Defaults to
         * an empty array.
         * @var array
         */
        public $headers = array();

        /**
         * Request method. Defaults to GET.
         * @var string
         */
        public $method = 'GET';

        /**
         * Request query string.
         * @var string
         */
        public $queryString;

        /**
         * Request URI without query string.
         * @var string
         */
        public $uri;

        /**
         * Computed request URL, to be used mostly by HAL classes to compute links.
         * @var string
         */
        public $url;


        /**
         * Request constructor.
         * @param bool $from_environment
         */
        public function __construct(bool $from_environment)
        {
            if (!$from_environment) {

                return;

            }

            if (function_exists('apache_request_headers')) {

                $this->headers = apache_request_headers();

            }

            $this->queryString = $_SERVER['QUERY_STRING'] ?? '';

            $this->method = $_SERVER['REQUEST_METHOD'];
            $this->uri = str_replace('?'.$this->queryString, '', $_SERVER['REQUEST_URI']);
            $this->url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/'.$this->uri;

            // Using php://input stream allows retrieval of request body with all HTTP methods (not only POST)
            $this->body = file_get_contents('php://input');
        }

        /**
         * Returns an indexed array where values are associatives arrays containing two key-value pairs :
         * - media : contains accepted media type;
         * - quality : contains quality score for media type.
         *
         * Returned array is sorted descending according to quality score (so preferred media type will be at index 0).
         *
         * If request has no Accept header, this method will return an empty array.
         * @return array
         */
        public function parseAcceptHeader():array
        {
            return $this->parseAnyAcceptHeader('Accept', 'media');
        }

        /**
         * Parses any Accept header, extracting entity and quality score to return them as an indexed array of
         * associative arrays containing two key-value pairs:
         * - $entity_key (precised by parameter): contains accepted entity;
         * - quality: contains quality score for entity.
         *
         * Returned array is sorted descending according to quality score (so preferred entity will be at index 0).
         *
         * If request has no $header (precised by parameter), this method will return an empty array.
         * @param string $header
         * @param string $entity_key
         * @return array
         */
        protected function parseAnyAcceptHeader(string $header, string $entity_key):array
        {
            $return = [];

            if (!array_key_exists($header, $this->headers)) {

                return $return;

            }

            $explode = explode(',', $this->headers[$header]);

            $quality = 1;
            foreach ($explode as $chunk) {

                $chunk = explode(';', $chunk);
                $return[] = [
                    $entity_key => trim($chunk[0]),
                    'quality' => isset($chunk[1]) ? (float) str_replace('q=', '', $chunk[1]) : $quality
                ];

                $quality -= 0.1;

            }

            usort($return, function($a, $b) {

                return ($a['quality'] > $b['quality']) ? 1 : ($a['quality'] < $b['quality']) ? -1 : 0;

            });

            return $return;
        }

        /**
         * Returns an indexed array where values are associatives arrays containing two key-value pairs :
         * - lang : contains accepted language range;
         * - quality : contains quality score for language range.
         *
         * Returned array is sorted descending according to quality score (so preferred language range will be at index
         * 0).
         *
         * If request has no Accept-Language header, this method will return an empty array.
         * @return array
         */
        public function parseAcceptLanguageHeader():array
        {
            return $this->parseAnyAcceptHeader('Accept-Language', 'lang');
        }

        /**
         * Parse request body according to Content-Type header.
         * @return int
         */
        public function parseRequestBody():int
        {
            // Nothing to cast or to cast against
            if (empty($this->body) || !array_key_exists('Content-Type', $this->headers)) {

                return self::PARSE_200;

            }

            // Behavior expected by PSR-7
            if ($this->method === 'POST'
                && array_key_exists('Content-Type', $this->headers)
                && in_array($this->headers['Content-Type'], array(
                    'multipart/form-data',
                    'application/x-www-form-urlencoded'
                ))
            ) {

                $this->body = $_POST;
                $this->files = $_FILES;

                return self::PARSE_200;

            }

            /*
             * Value 'multipart/form-data' (e.g. for file uploading) for Content-Type header is only supported on POST
             * requests. File uploading can also be done with a PUT request, but PUT-ing a file through a REST API
             * implies that file would be available at requested endpoint, and this behavior is mostly unwanted.
             */
            if ($this->headers['Content-Type'] == 'multipart/form-data' && $this->method !== 'POST') {

                return self::PARSE_405;

            }

            if ($this->headers['Content-Type'] === 'application/x-www-form-urlencoded') {

                parse_str($this->body, $this->body);
                return self::PARSE_200;

            }

            $try = json_decode($this->body, true);
            if ($try !== null){

                $this->body = $try;
                array_walk_recursive($this->body, function(&$value){

                    $value = trim($value);

                });

                return self::PARSE_200;

            } else {

                return self::PARSE_415;

            }
        }
    }
