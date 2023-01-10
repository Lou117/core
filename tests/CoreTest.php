<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 23/07/2018
 * Time: 16:27
 */

use Monolog\Logger;
use Lou117\Core\Core;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\ServerRequest;
use Lou117\Core\Container\Container;
use Psr\Http\Message\RequestInterface;

require (__DIR__.'/TestController.php');
require (__DIR__.'/TestMiddlewareFoo.php');
require (__DIR__.'/TestMiddlewareBar.php');
require_once (__DIR__.'/Routing/NestedTableParserTest.php');

/**
 * PHPUnit test cases for Core and RequestHandler classes.
 */
class CoreTest extends TestCase
{
    /**
     * Generates a temporary file to be used as configuration, and returns generated file path.
     *
     * @param bool $create_file - must file be created? (allows for testing with non-existent configuration file,
     * defaults to TRUE)
     * @param bool $empty_file - must file be empty? (allows for testing with invalid configuration file, defaults to
     * FALSE)
     * @param bool $with_mw_sequence - must configuration be containing middleware sequence? (allows for testing with or
     * without middleware sequence, defaults to FALSE)
     * @return string
     */
    protected function generateConfigurationFile(bool $create_file = true, bool $empty_file = false, bool $with_mw_sequence = false): string
    {
        $configurationFilename = "/tmp/".uniqid();

        if ($create_file) {
            $configurationFile = fopen($configurationFilename, "w+");

            if ($empty_file === false) {

                if ($with_mw_sequence) {
                    fwrite($configurationFile, "<?php return [
                        'logger' => [
                            'class' => ['Monolog\Handler\RotatingFileHandler', ['/tmp/log', 1]]
                        ],
                        'mw-sequence' => [
                            TestMiddlewareFoo::class,
                            TestMiddlewareBar::class
                        ]
                    ];");
                } else {
                    fwrite($configurationFile, "<?php return [
                        'logger' => [
                            'class' => ['Monolog\Handler\RotatingFileHandler', ['/tmp/log', 1]]
                        ]
                    ];");
                }

            }
        }

        return $configurationFilename;
    }

    /**
     * @return Core
     */
    public function testCoreInstantiationWithoutParameters(): Core
    {
        $core = new Core();
        $this->assertInstanceOf(Core::class, $core);

        return $core;
    }

    /**
     * @depends testCoreInstantiationWithoutParameters
     * @param Core $core
     */
    public function testConfigurationLoadingWithNonExistentFile(Core $core)
    {
        $this->expectException(InvalidArgumentException::class);
        $core->loadConfigurationFile($this->generateConfigurationFile(false));
    }

    /**
     * @depends testCoreInstantiationWithoutParameters
     * @param Core $core
     */
    public function testRoutingTableLoadingWithNonExistentFile(Core $core)
    {
        $this->expectException(InvalidArgumentException::class);
        $core->loadRoutingTableFile(NestedTableParserTest::generateRoutingTableFile(false));
    }

    /**
     * @return Core
     */
    public function testCoreInstantiationWithParameters(): Core
    {
        $core = new Core($this->generateConfigurationFile(), NestedTableParserTest::generateRoutingTableFile());
        $this->assertInstanceOf(Core::class, $core);

        return $core;
    }

    /**
     * @depends testCoreInstantiationWithParameters
     * @param Core $core
     * @return Core
     */
    public function testCoreContainerAfterInstantiation(Core $core): Core
    {
        $container = $core->container;
        $this->assertInstanceOf(Container::class, $container);

        $this->assertTrue(is_array($container->get("core.configuration")));
        $this->assertNotEmpty($container->get("core.configuration"));

        $this->assertInstanceOf(Logger::class, $container->get("core.logger"));
        $this->assertTrue(count($container->get("core.logger")->getHandlers()) > 0);

        $this->assertInstanceOf(RequestInterface::class, $container->get("core.request"));

        return $core;
    }

    /**
     * @depends testCoreInstantiationWithParameters
     * @param Core $core
     * @throws Exception
     */
    public function testCoreRunInto404(Core $core)
    {
        $response = $core->run(new ServerRequest("GET", "/not-found"), true);
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @depends testCoreInstantiationWithParameters
     * @param Core $core
     * @throws Exception
     */
    public function testCoreRunInto405(Core $core)
    {
        $response = $core->run(new ServerRequest("POST", "/not-allowed"), true);
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals("GET", $response->getHeaderLine("Allow"));
    }

    /**
     * @depends testCoreInstantiationWithParameters
     * @param Core $core
     * @throws Exception
     */
    public function testCoreWithNoMiddleware(Core $core)
    {
        $response = $core->run(new ServerRequest("GET", "/valid-controller"), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->hasHeader("X-Controller-Test"));
    }

    /**
     * @depends testCoreContainerAfterInstantiation
     * @param Core $core
     * @throws Exception
     */
    public function testCoreWithInvalidControllerClass(Core $core)
    {
        $this->expectException(RuntimeException::class);
        $core->run(new ServerRequest("GET", "/invalid-controller-class"));
    }

    /**
     * @depends testCoreContainerAfterInstantiation
     * @param Core $core
     * @throws Exception
     */
    public function testCoreWithInvalidControllerMethod(Core $core)
    {
        $this->expectException(BadMethodCallException::class);
        $core->run(new ServerRequest("GET", "/invalid-controller-method"));
    }

    /**
     * @depends testCoreContainerAfterInstantiation
     * @param Core $core
     * @throws Exception
     */
    public function testCoreWithInvalidControllerDeclaration(Core $core)
    {
        $this->expectException(RuntimeException::class);
        $core->run(new ServerRequest("GET", "/invalid-controller-declaration"));
    }

    /**
     * @depends testCoreInstantiationWithParameters
     * @param Core $core
     * @throws Exception
     */
    public function testCoreWithMiddlewareSequence(Core $core)
    {
        $core->loadConfigurationFile($this->generateConfigurationFile(true, false, true));

        $response = $core->run(new ServerRequest("GET", "/valid-controller"), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->hasHeader("X-Controller-Test"));
        $this->assertTrue($response->hasHeader("X-MiddlewareFoo-Test"));
        $this->assertTrue($response->hasHeader("X-MiddlewareBar-Test"));
    }
}
