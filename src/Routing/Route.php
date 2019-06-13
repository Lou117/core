<?php
    namespace Lou117\Core\Routing;

    /**
     * Describes a route.
     *
     * @package Lou117\Core
     */
    class Route
    {
        /**
         * @var mixed[]
         */
        public $arguments;

        /**
         * @var mixed[]
         */
        public $attributes;

        /**
         * @var string
         */
        public $controller;

        /**
         * @var string
         */
        public $endpoint;

        /**
         * @var string[]
         */
        public $methods;
    }
