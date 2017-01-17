<?php
    namespace Lou117\Core\Exchange\Hal;

    class HalCollection extends HalResource
    {
        /**
         * Collection links as an associative array where keys are link type and values are URL.
         * @var array
         */
        protected $links = [
            'first' => '',
            'prev' => '',
            'self' => '',
            'next' => '',
            'last' => ''
        ];
    }
