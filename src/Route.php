<?php
    namespace Lou117\Core;
    
    class Route
    {
        /**
         * @var string
         */
        public $name;
    
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
        public $moduleNamespace;
    
        /**
         * @var [mixed]
         */
        public $uriData;
    }
