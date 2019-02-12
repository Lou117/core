<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 23/07/2018
 * Time: 16:27
 */

use Monolog\Logger;
use Lou117\Core\Core;
use FastRoute\Dispatcher;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\ServerRequest;
use Lou117\Core\Container\Container;
use Psr\Http\Message\RequestInterface;

require (__DIR__.'/TestController.php');
require (__DIR__.'/TestMiddlewareFoo.php');
require (__DIR__.'/TestMiddlewareBar.php');

/**
 * PHPUnit test cases for Core and RequestHandler classes.
 */
class CoreTest extends TestCase
{
    protected function generateSettingsFile(bool $create_file = true, bool $empty_file = false, bool $with_mw_sequence = false): string
    {
        $settingsFilename = "/tmp/".uniqid();
        if ($create_file) {

            $settingsFile = fopen($settingsFilename, "w+");
            if ($empty_file === false) {

                if ($with_mw_sequence) {

                    fwrite($settingsFile, "<?php return [
                        'mw-sequence' => [
                            TestMiddlewareFoo::class,
                            TestMiddlewareBar::class
                        ],
                        'logger' => [
                            'class' => ['Monolog\Handler\RotatingFileHandler', ['/tmp/log', 1]]
                        ]
                    ] ?>");

                } else {

                    fwrite($settingsFile, "<?php return [
                        'logger' => [
                            'class' => ['Monolog\Handler\RotatingFileHandler', ['/tmp/log', 1]]
                        ]
                    ] ?>");

                }

            }

        }

        return $settingsFilename;
    }

    protected function generateRoutingTableFile($create_file = true, $empty_file = false): string
    {
        $routingTableFilename = "/tmp/".uniqid();

        if ($create_file) {
            $routingTableFile = fopen($routingTableFilename, "w+");

            if ($empty_file === false) {
                fwrite($routingTableFile, "<?php return [
                    '/not-allowed' => [
                        'methods' => ['GET'],
                        'controller' => 'NotReachedController'
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
            }
        }

        return $routingTableFilename;
    }

    /**
     * @throws \Lou117\Core\Exception\InvalidSettingsException
     * @return Core
     */
    public function testCoreInstantiation(): Core
    {
        $core = new Core($this->generateSettingsFile(), $this->generateRoutingTableFile());
        $this->assertInstanceOf(Core::class, $core);

        return $core;
    }

    /**
     * @expectedException \Lou117\Core\Exception\SettingsNotFoundException
     * @throws \Lou117\Core\Exception\InvalidSettingsException
     */
    public function testCoreInstantiationWithSettingsNotFound()
    {
        new Core(
            $this->generateSettingsFile(false),
            $this->generateRoutingTableFile()
        );
    }

    /**
     * @expectedException \Lou117\Core\Exception\InvalidSettingsException
     * @throws \Lou117\Core\Exception\InvalidSettingsException
     */
    public function testCoreInstantiationWithInvalidSettings()
    {
        new Core(
            $this->generateSettingsFile(true, true),
            $this->generateRoutingTableFile()
        );
    }

    /**
     * @expectedException \Lou117\Core\Exception\RoutingTableNotFoundException
     * @throws \Lou117\Core\Exception\InvalidSettingsException
     */
    public function testCoreInstantiationWithRoutingTableNotFound()
    {
        new Core(
            $this->generateSettingsFile(),
            $this->generateRoutingTableFile(false)
        );
    }

    /**
     * @expectedException \LogicException
     * @throws \Lou117\Core\Exception\InvalidSettingsException
     */
    public function testCoreInstantiationWithInvalidRoutingTable()
    {
        new Core(
            $this->generateSettingsFile(),
            $this->generateRoutingTableFile(true, true)
        );
    }

    /**
     * @depends testCoreInstantiation
     * @param Core $core
     * @return Core
     */
    public function testCoreContainerAfterInstantiation(Core $core): Core
    {
        $container = $core->getContainer();
        $this->assertInstanceOf(Container::class, $container);

        $this->assertTrue(is_array($container->get("settings")));
        $this->assertNotEmpty($container->get("settings"));

        $this->assertInstanceOf(Logger::class, $container->get("core-logger"));
        $this->assertTrue(count($container->get("core-logger")->getHandlers()) > 0);

        $this->assertInstanceOf(Dispatcher::class, $container->get("router"));

        $this->assertInstanceOf(RequestInterface::class, $container->get("request"));

        return $core;
    }

    /**
     * @depends testCoreContainerAfterInstantiation
     * @param Core $core
     * @throws Exception
     */
    public function testCoreRunInto404(Core $core)
    {
        $response = $core->run(new ServerRequest("GET", "/not-found"), true);
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @depends testCoreContainerAfterInstantiation
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
     * @depends testCoreContainerAfterInstantiation
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
     * @expectedException RuntimeException
     * @param Core $core
     * @throws Exception
     */
    public function testCoreWithInvalidControllerClass(Core $core)
    {
        $core->run(new ServerRequest("GET", "/invalid-controller-class"));
    }

    /**
     * @depends testCoreContainerAfterInstantiation
     * @expectedException BadMethodCallException
     * @param Core $core
     * @throws Exception
     */
    public function testCoreWithInvalidControllerMethod(Core $core)
    {
        $core->run(new ServerRequest("GET", "/invalid-controller-method"));
    }

    /**
     * @depends testCoreContainerAfterInstantiation
     * @expectedException RuntimeException
     * @param Core $core
     * @throws Exception
     */
    public function testCoreWithInvalidControllerDeclaration(Core $core)
    {
        $core->run(new ServerRequest("GET", "/invalid-controller-declaration"));
    }

    /**
     * @depends testCoreWithNoMiddleware
     * @throws Exception
     */
    public function testCoreWithMiddlewareSequence()
    {
        $core = new Core(
            $this->generateSettingsFile(true, false, true),
            $this->generateRoutingTableFile()
        );

        $response = $core->run(new ServerRequest("GET", "/valid-controller"), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->hasHeader("X-Controller-Test"));
        $this->assertTrue($response->hasHeader("X-MiddlewareFoo-Test"));
        $this->assertTrue($response->hasHeader("X-MiddlewareBar-Test"));
    }
}
