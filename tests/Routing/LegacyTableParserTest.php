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
use Lou117\Core\Routing\LegacyTableParser;
use Lou117\Core\Routing\AbstractTableParser;

class LegacyTableParserTest extends TestCase
{
    /**
     * Generates a temporary file to be used legacy routing table, and returns generated file path.
     *
     * @param bool $create_file - should file be created? (allows for tests with non-existent file, defaults to TRUE)
     * @param bool $empty_file - should file be empty? (allows for tests with invalid file content, defaults to FALSE)
     * @return string
     */
    protected function generateRoutingTableFile($create_file = true, $empty_file = false): string
    {
        $routingTableFilename = "/tmp/".uniqid();

        if ($create_file) {
            $routingTableFile = fopen($routingTableFilename, "w+");

            if ($empty_file === false) {
                fwrite($routingTableFile, "<?php return [
                    'route1' => [
                        'methods' => ['GET'],
                        'endpoint' => '/route1',
                        'controller' => 'ControllerClass'
                    ],
                    'route2' => [
                        'methods' => ['GET'],
                        'endpoint' => '/route2/{foo}',
                        'controller' => 'ControllerClass',
                        'arguments' => [
                            'foo' => '1'
                        ],
                        'bar' => '2'
                    ]
                ] ?>");
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
        $this->assertTrue(is_a(LegacyTableParser::class, AbstractTableParser::class, true));
    }

    /**
     * @depends testParserExtendsAbstractTableParser
     * @doesNotPerformAssertions
     * @return LegacyTableParser
     */
    public function testParserInstantiation()
    {
        $parser = new LegacyTableParser(new Logger("tmp", [new NullHandler()]));
        return $parser;
    }

    /**
     * @depends testParserInstantiation
     * @param LegacyTableParser $parser
     * @expectedException LogicException
     */
    public function testParserThrowsExceptionOnInvalidRoutingTable(LegacyTableParser $parser)
    {
        $parser->parse(self::generateRoutingTableFile(true, true));
    }

    /**
     * @depends testParserInstantiation
     * @param LegacyTableParser $parser
     */
    public function testParsing(LegacyTableParser $parser)
    {
        $routes = $parser->parse(self::generateRoutingTableFile());

        $this->assertCount(2, $routes);

        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
        }

        $this->assertTrue($this->has($routes, '/route1', ['GET']));
        $this->assertFalse($this->has($routes, '/route1', ['DELETE']));
        $this->assertTrue($this->has($routes, '/route2/{foo}', ['GET']));

        $route = $this->get($routes, '/route2/{foo}', ['GET']);
        $this->assertArrayHasKey('foo', $route->arguments);
        $this->assertEquals(1, $route->arguments['foo']);
        $this->assertArrayHasKey('bar', $route->attributes);
        $this->assertEquals(2, $route->attributes['bar']);
    }
}
