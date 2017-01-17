<?php
    namespace Lou117\Core\Exchange\Hal;

    class HalResource implements \JsonSerializable
    {
        /**
         * Embedded resources as an associative array where keys are resource type and values are indexed arrays of
         * resources.
         * @var array
         */
        protected $embedded = [];

        /**
         * Resource links as an associative array where keys are link type and values are URL.
         * @var array
         */
        protected $links = [
            'self' => null
        ];

        /**
         * Resource state as key-value pairs.
         * @var array
         */
        protected $state = [];


        /**
         * Add embedded resource to current resource.
         * @param string $resource_type
         * @param HalResource $resource
         * @return HalResource
         */
        public function addEmbedded(string $resource_type, HalResource $resource):HalResource
        {
            $this->embedded[$resource_type][] = $resource;
            return $this;
        }

        /**
         * Add link to current resource.
         * @param string $link_type
         * @param string $url
         * @return HalResource
         */
        public function addLink(string $link_type, string $url):HalResource
        {
            $this->links[$link_type] = $url;
            return $this;
        }

        /**
         * Add state to current resource.
         * @param string $key
         * @param $value
         * @return HalResource
         */
        public function addState(string $key, $value):HalResource
        {
            $this->state[$key] = $value;
            return $this;
        }

        /**
         * Returns an array that can be encoded to HAL JSON.
         * @return array
         */
        public function jsonSerialize()
        {
            $return = [
                '_links' => []
            ];

            foreach ($this->links as $link_type => $url) {

                $return['_links'][$link_type]['href'] = $url;

            }

            foreach ($this->state as $key => $value) {

                $return[$key] = $value;

            }

            if (!empty($this->embedded)) {

                $return['_embedded'] = $this->embedded;

            }

            return $return;
        }
    }
