<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 2019-02-12
 * Time: 13:27
 */
use Lou117\Core\Route;
use PHPUnit\Framework\TestCase;
use Lou117\Core\RoutingTableParser;
use Lou117\Core\AbstractRoutingTableParser;

class RoutingTableParserTest extends TestCase
{
    /**
     * @param bool $create_file
     * @param bool $empty_file
     * @param bool $nested
     * @return string
     */
    static public function generateRoutingTableFile($create_file = true, $empty_file = false, $nested = false): string
    {
        $routingTableFilename = "/tmp/".uniqid();

        if ($create_file) {
            $routingTableFile = fopen($routingTableFilename, "w+");

            if ($empty_file === false) {

                if (!$nested) {
                    fwrite($routingTableFile, "<?php return [
                        '/not-allowed' => [
                            'methods' => ['GET'],
                            'controller' => 'NeverReachedController'
                        ],
                        '/invalid-controller-declaration' => [
                            'methods' => ['GET'],
                            'controller' => 'InvalidControllerfoo'
                        ],
                        '/invalid-controller-class' => [
                            'methods' => ['GET'],
                            'controller' => 'InvalidController::foo'
                        ],
                        '/invalid-controller-method' => [
                            'methods' => ['GET'],
                            'controller' => 'TestController::bar'
                        ],
                        '/valid-controller' => [
                            'methods' => ['GET'],
                            'controller' => 'TestController::foo'
                        ],
                        '/invalid-controller-method-return' => [
                            'methods' => ['GET'],
                            'controller' => 'TestController::baz'
                        ]
                    ] ?>");
                } else {
                    fwrite($routingTableFile, "<?php return [
                        'without-starting-slash' => [
                            'methods' => ['GET'],
                            'controller' => 'NeverReachedController'
                        ],
                        '/with-trailing-slash/' => [
                            'methods' => ['GET'],
                            'controller' => 'NeverReachedController'
                        ],
                        '/nested' => [
                            'methods' => ['GET', 'POST'],
                            'controller' => [
                                'GET' => 'NeverReachedController',
                                'POST' => 'NeverReachedController',
                                'DELETE' => 'NeverReachedController'
                            ],
                            'children' => [
                                '/child' => [
                                    'methods' => ['GET'],
                                    'controller' => 'NeverReachedController',
                                    'arguments' => [
                                        'one' => false
                                    ],
                                    'attrOne' => false
                                ]
                            ],
                            'arguments' => [
                                'one' => true,
                                'two' => false 
                            ],
                            'attrOne' => true,
                            'attrTwo' => false
                        ]
                    ];");
                }
            }
        }

        return $routingTableFilename;
    }

    /**
     * @param array $routes
     * @param string $expected_endpoint
     * @param array $expected_methods
     * @return Route|null
     */
    protected function get(array $routes, string $expected_endpoint, array $expected_methods): ?Route
    {
        foreach ($routes as $route) {
            if ($expected_endpoint !== $route->endpoint) {
                continue;
            }
            $hasAllMethods = true;
            foreach ($expected_methods as $expected_method) {
                if (!in_array($expected_method, $route->methods)) {
                    $hasAllMethods = false;
                }
            }
            if ($hasAllMethods) {
                return $route;
            }
        }

        return null;
    }

    /**
     * @param Route[] $routes
     * @param string $expected_endpoint
     * @param string[] $expected_methods
     * @return bool
     */
    protected function has(array $routes, string $expected_endpoint, array $expected_methods)
    {
        return $this->get($routes, $expected_endpoint, $expected_methods) instanceof Route;
    }

    public function testRoutingParserExtendsAbstractRoutingTableParser()
    {
        $routingTableParser = new RoutingTableParser();
        $this->assertInstanceOf(AbstractRoutingTableParser::class, $routingTableParser);
        return $routingTableParser;
    }

    /**
     * @depends testRoutingParserExtendsAbstractRoutingTableParser
     * @param RoutingTableParser $routing_table_parser
     */
    public function testRoutingParser(RoutingTableParser $routing_table_parser)
    {
        $routes = $routing_table_parser->parse(self::generateRoutingTableFile(true, false, true));
        $this->assertCount(5, $routes);
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
        }

        $this->assertSame("/without-starting-slash", $routes[0]->endpoint);
        $this->assertSame("/with-trailing-slash", $routes[1]->endpoint);

        $this->assertTrue($this->has($routes, '/nested', ['GET']));
        $this->assertTrue($this->has($routes, '/nested', ['POST']));
        $this->assertFalse($this->has($routes, '/nested', ['DELETE']));
        $this->assertTrue($this->has($routes, '/nested/child', ['GET']));

        $route = $this->get($routes, '/nested', ['GET']);
        $this->assertArrayHasKey('one', $route->arguments);
        $this->assertTrue($route->arguments['one']);
        $this->assertArrayHasKey('two', $route->arguments);
        $this->assertFalse($route->arguments['two']);
        $this->assertArrayHasKey('attrOne', $route->attributes);
        $this->assertTrue($route->attributes['attrOne']);
        $this->assertArrayHasKey('attrTwo', $route->attributes);
        $this->assertFalse($route->attributes['attrTwo']);

        $route = $this->get($routes, '/nested/child', ['GET']);
        $this->assertArrayHasKey('one', $route->arguments);
        $this->assertFalse($route->arguments['one']);
        $this->assertArrayHasKey('two', $route->arguments);
        $this->assertFalse($route->arguments['two']);

        $this->assertArrayHasKey('attrOne', $route->attributes);
        $this->assertFalse($route->attributes['attrOne']);
        $this->assertArrayHasKey('attrTwo', $route->attributes);
        $this->assertFalse($route->attributes['attrTwo']);
    }

    /**
     * @depends testRoutingParserExtendsAbstractRoutingTableParser
     * @expectedException LogicException
     * @param RoutingTableParser $routing_table_parser
     */
    public function testRoutingParserWithEmptyFile(RoutingTableParser $routing_table_parser)
    {
        $routing_table_parser->parse(self::generateRoutingTableFile(true, true));
    }
}
