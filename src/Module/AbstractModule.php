<?php
    namespace Lou117\Core\Module;

    use Lou117\Core\Route;
    use Lou117\Core\Http\Request;
    use Lou117\Core\Http\Response\AbstractResponse;

    abstract class AbstractModule
    {
        /**
         * HTTP request.
         * @var Request
         */
        public $request;

        /**
         * Route configuration.
         * @var Route
         */
        public $route;


        /**
         * AbstractModule constructor.
         * @param Request $request - Request built by Core.
         * @param Route $route - Matched route configuration.
         */
        final public function __construct(Request $request, Route $route)
        {
            $this->route = $route;
            $this->request = $request;
        }

        /**
         * Runs module logic.
         * @return AbstractResponse
         */
        abstract public function run(): AbstractResponse;
    }
