<?php
    /**
     * Created by PhpStorm.
     * User: sylvain
     * Date: 04/04/2017
     * Time: 01:21
     */

    namespace Lou117\Core\Module;

    class ModuleMetadata
    {
        /**
         * @var string
         */
        public $fqcn;

        /**
         * @var string
         */
        public $name;

        /**
         * @var string
         */
        public $namespace;

        /**
         * @var string[]
         */
        public $services;
    }
