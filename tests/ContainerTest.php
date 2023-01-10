<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 08/07/2018
 * Time: 19:52
 */

use Lou117\Core\Container\NotFoundException;
use PHPUnit\Framework\TestCase;
use Lou117\Core\Container\Container;

class ContainerTest extends TestCase
{
    /**
     * @return Container
     */
    public function testContainerInstantiation(): Container
    {
        $container = new Container();
        $this->assertInstanceOf(Container::class, $container);
        return $container;
    }

    /**
     * @depends testContainerInstantiation
     * @param Container $container
     * @return Container
     */
    public function testContainerSet(Container $container)
    {
        $container->set("test", "test");
        $this->assertTrue($container->has("test"));
        return $container;
    }

    /**
     * @param Container $container
     * @depends testContainerSet
     */
    public function testContainerGet(Container $container)
    {
        $this->assertEquals("test", $container->get("test"));
    }

    /**
     * @depends testContainerSet
     * @param Container $container
     */
    public function testContainerDuplicate(Container $container)
    {
        $this->expectException(InvalidArgumentException::class);
        $container->set("test", "test", true);
    }

    /**
     * @depends testContainerInstantiation
     * @param Container $container
     */
    public function testContainerNotFound(Container $container)
    {
        $this->expectException(NotFoundException::class);
        $container->get("notfound");
    }
}
