<?php
    namespace Lou117\Core;
    
    use Lou117\Core\Http\Request;
    use Lou117\Core\Http\Response;

    class AbstractModule
    {
        /**
         * HTTP request.
         * @var Request
         */
        public $request;
    
        /**
         * HTTP response.
         * @var Response
         */
        public $response;
    
        /**
         * Route configuration.
         * @var Route
         */
        public $route;
    
    
        /**
         * AbstractModule constructor.
         * @param Request $request - Request built by Core.
         * @param Response $response - Response built by Core.
         * @param Route $route - Matched route configuration.
         */
        public function __construct(Request $request, Response $response, Route $route)
        {
            $this->route = $route;
            $this->request = $request;
            $this->response = $response;
        }
    }
