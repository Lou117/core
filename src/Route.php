<?php
    namespace Lou117\Core;

    class Route
    {
        /**
         * @var [string]
         */
        public $allowedMethods;

        /**
         * @var string
         */
        public $endpoint;

        /**
         * @var string
         */
        public $fullname;

        /**
         * @var Module
         */
        public $module;

        /**
         * @var string
         */
        public $name;

        /**
         * @var [mixed]
         */
        public $uriData;
    }
