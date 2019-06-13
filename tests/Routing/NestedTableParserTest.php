<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 2019-02-12
 * Time: 13:27
 */
use Monolog\Logger;
use Lou117\Core\Routing\Route;
use PHPUnit\Framework\TestCase;
use Monolog\Handler\NullHandler;
use Lou117\Core\Routing\NestedTableParser;
use Lou117\Core\Routing\AbstractTableParser;

class NestedTableParserTest extends TestCase
{
    /**
     * Generates a temporary file to be used as routing table, and returns generated file path.
     *
     * This method is static in order to be used easily by CoreTest class.
     *
     * @param bool $create_file - should file be created? (allows for tests with non-existent file, defaults to TRUE)
     * @param bool $empty_file - should file be empty? (allows for tests with invalid file content, defaults to FALSE)
     * @return string
     */
    static public function generateRoutingTableFile($create_file = true, $empty_file = false): string
    {
        $routingTableFilename = "/tmp/".uniqid();

        if ($create_file) {
            $routingTableFile = fopen($routingTableFilename, "w+");

            if ($empty_file === false) {
                fwrite($routingTableFile, "<?php return [
                    'without-starting-slash' => [
                        'methods' => ['GET'],
                        'controller' => 'NeverReachedController'
                    ],
                    '/with-trailing-slash/' => [
                        'methods' => ['GET'],
                        'controller' => 'NeverReachedController'
                    ],
                    '/not-allowed' => [
                        'methods' => ['GET'],
                        'controller' => 'NeverReachedController'
                    ],
                    '/valid-controller' => [
                        'methods' => ['GET'],
                        'controller' => 'TestController::foo'
                    ],
                    '/invalid-controller-declaration' => [
                        'methods' => ['GET'],
                        'controller' => 'InvalidControllerfoo'
                    ],
                    '/invalid-controller-method' => [
                        'methods' => ['GET'],
                        'controller' => 'TestController::bar'
                    ],
                    '/invalid-controller-class' => [
                        'methods' => ['GET'],
                        'controller' => 'InvalidController::foo'
                    ],
                    '/invalid-controller-method-return' => [
                        'methods' => ['GET'],
                        'controller' => 'TestController::baz'
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

        return $routingTableFilename;
    }

    /**
     * Searches amongst given $routes for a Route instance corresponding to given $expected_endpoint, and ALL of given
     * $expected_methods. Returns found Route instance, if any ; NULL if no Route instance is corresponding.
     *
     * @param Route[] $routes
     * @param string $expected_endpoint
     * @param string[] $expected_methods
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
     * Searches amongst given $routes for a Route instance corresponding to given $expected_endpoint, and ALL of given
     * $expected_methods. Returns a boolean indicating whether or not a corresponding Route instance was found.
     *
     * @param Route[] $routes
     * @param string $expected_endpoint
     * @param string[] $expected_methods
     * @return bool
     */
    protected function has(array $routes, string $expected_endpoint, array $expected_methods)
    {
        return $this->get($routes, $expected_endpoint, $expected_methods) instanceof Route;
    }

    public function testParserExtendsAbstractTableParser()
    {
        $this->assertTrue(is_a(NestedTableParser::class, AbstractTableParser::class, true));
    }

    /**
     * @depends testParserExtendsAbstractTableParser
     * @doesNotPerformAssertions
     * @return NestedTableParser
     */
    public function testParserInstantiation()
    {
        $parser = new NestedTableParser(new Logger("tmp", [new NullHandler()]));
        return $parser;
    }

    /**
     * @depends testParserInstantiation
     * @param NestedTableParser $parser
     * @expectedException LogicException
     */
    public function testParserThrowsExceptionOnInvalidRoutingTable(NestedTableParser $parser)
    {
        $parser->parse(self::generateRoutingTableFile(true, true));
    }

    /**
     * @depends testParserInstantiation
     * @param NestedTableParser $parser
     */
    public function testParsing(NestedTableParser $parser)
    {
        $routes = $parser->parse(self::generateRoutingTableFile(true, false));

        $this->assertCount(11, $routes);

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
}
