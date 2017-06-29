<?php
    namespace Lou117\Core;

    use Lou117\Core\Module\ModuleMetadata;

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
         * @var ModuleMetadata
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
