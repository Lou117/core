<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 08/07/2018
 * Time: 20:05
 */
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Lou117\Core\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

class ResponseFactoryTest extends TestCase
{
    public function testCreateHtmlResponse()
    {
        $code = 201;
        $body = "test";

        $response = ResponseFactory::createHtmlResponse($body, $code);
        $this->assertInstanceOf(ResponseInterface::class, $response);

        $this->assertEquals("text/html", $response->getHeaderLine("Content-Type"));
        $this->assertEquals($body, $response->getBody()->read($response->getBody()->getSize()));
        $this->assertEquals($code, $response->getStatusCode());
    }

    public function testCreateHtmlResponseWithEmptyString()
    {
        $response = ResponseFactory::createTextResponse("\n\t  ");
        $this->assertInstanceOf(ResponseInterface::class, $response);

        $this->assertFalse($response->hasHeader("Content-Type"));
        $this->assertEmpty($response->getBody()->read($response->getBody()->getSize()));
    }

    public function testCreateJsonResponse()
    {
        $code = 201;
        $json = ["test" => "test"];

        $response = ResponseFactory::createJsonResponse($json, $code);
        $this->assertInstanceOf(ResponseInterface::class, $response);

        $this->assertEquals("application/json", $response->getHeaderLine("Content-Type"));
        $this->assertEquals($json, json_decode($response->getBody()->read($response->getBody()->getSize()), true));
        $this->assertEquals($code, $response->getStatusCode());
    }

    /**
     * @expectedException InvalidArgumentException
     * @requires function imagecreate
     */
    public function testCreateJsonResponseWithInvalidJson()
    {
        $resource = imagecreate(16, 16);
        ResponseFactory::createJsonResponse($resource);
    }

    public function testCreateTextResponse()
    {
        $code = 201;
        $body = "test";

        $response = ResponseFactory::createTextResponse($body, $code);
        $this->assertInstanceOf(ResponseInterface::class, $response);

        $this->assertEquals("text/plain", $response->getHeaderLine("Content-Type"));
        $this->assertEquals($body, $response->getBody()->read($response->getBody()->getSize()));
        $this->assertEquals($code, $response->getStatusCode());
    }

    public function testCreateTextResponseWithEmptyString()
    {
        $response = ResponseFactory::createTextResponse("\n\t  ");
        $this->assertInstanceOf(ResponseInterface::class, $response);

        $this->assertFalse($response->hasHeader("Content-Type"));
        $this->assertEmpty($response->getBody()->read($response->getBody()->getSize()));
    }

    public function testCreateRedirectResponse()
    {
        $location = "https://google.com";

        $response = ResponseFactory::createRedirectResponse($location);
        $this->assertInstanceOf(ResponseInterface::class, $response);

        $this->assertEquals($location, $response->getHeaderLine("Location"));
        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateRedirectResponseWithEmptyLocation()
    {
        ResponseFactory::createRedirectResponse("");
    }

    public function testIsEmptyResponse()
    {
        $response = new Response();
        $this->assertFalse(ResponseFactory::isEmptyResponse($response));

        $response = $response->withStatus(204);
        $this->assertTrue(ResponseFactory::isEmptyResponse($response));

        $response = $response->withStatus(205);
        $this->assertTrue(ResponseFactory::isEmptyResponse($response));

        $response = $response->withStatus(304);
        $this->assertTrue(ResponseFactory::isEmptyResponse($response));
    }
}
