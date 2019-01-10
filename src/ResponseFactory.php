<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 08/07/2018
 * Time: 16:59
 */
namespace Lou117\Core;

use GuzzleHttp\Psr7\Response;
use \InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\Psr7\stream_for;

class ResponseFactory
{
    const HTTP_HEADER_ALLOW = "Allow";


    /**
     * Creates an HTML response (with Content-Type header set to "text/html").
     * @param string $body - Response body. If trim()-ed body is an empty string, neither Content-Type header nor body
     * will be added to returned response.
     * @param int $status - Response status (defaults to 200).
     * @return ResponseInterface
     */
    public static function createHTMLResponse(string $body, int $status = 200): ResponseInterface
    {
        $response = new Response($status);
        if (strlen(trim($body)) > 0) {

            $response = $response
                ->withHeader("Content-Type", "text/html")
                ->withBody(stream_for($body));

        }

        return $response;
    }

    /**
     * Creates a JSON response (with Content-Type header set to "application/json").
     * @param $body - Response body to be encoded to JSON. If JSON encoding results in a empty string, neither
     * Content-Type header nor body will be added to returned response.
     * @param int $status - Response status (defaults to 200).
     * @return ResponseInterface
     * @throws InvalidArgumentException - when given $body cannot be encoded to JSON (json_encode() returned NULL).
     */
    public static function createJSONResponse($body, int $status = 200): ResponseInterface
    {
        $encodedBody = json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($encodedBody === false) {

            throw new InvalidArgumentException("Given body cannot be encoded to JSON");

        }

        $response = new Response($status);
        if (strlen($encodedBody) > 0) {

            $response = $response
                ->withHeader("Content-Type", "application/json; charset=utf-8")
                ->withBody(stream_for($encodedBody));

        }

        return $response;
    }

    /**
     * Creates a redirect response, with status set to 302 and Location header set to given $location.
     * @param string $location - value for Location header.
     * @return ResponseInterface
     * @throws InvalidArgumentException - when given $location is an empty string.
     */
    public static function createRedirectResponse(string $location): ResponseInterface
    {
        if (empty($location)) {

            throw new InvalidArgumentException("Location header value cannot be empty");

        }

        $response = new Response(302);
        return $response->withHeader("Location", $location);
    }

    /**
     * Creates a text response (with Content-Type header set to "text/plain").
     * @param $body - Response body. If trim()-ed body is an empty string, neither Content-Type header nor body will be
     * added to returned response.
     * @param int $status - Response status (defaults to 200).
     * @return ResponseInterface
     */
    public static function createTextResponse(string $body, int $status = 200): ResponseInterface
    {
        $response = new Response($status);
        if (strlen(trim($body)) > 0) {

            $response = $response
                ->withHeader("Content-Type", "text/plain")
                ->withBody(stream_for($body));

        }

        return $response;
    }

    /**
     * Returns true if the provided response must not output a body and false
     * if the response could have a body.
     * @see https://tools.ietf.org/html/rfc7231
     * @param ResponseInterface $response
     * @return bool
     */
    public static function isEmptyResponse(ResponseInterface $response)
    {
        if (method_exists($response, 'isEmpty')) {

            return $response->isEmpty();

        }

        return in_array($response->getStatusCode(), [204, 205, 304]);
    }

    /**
     * Sends the response to the client.
     * @param ResponseInterface $response
     */
    public static function sendToClient(ResponseInterface $response)
    {
        if (!headers_sent()) {

            foreach ($response->getHeaders() as $name => $values) {

                // A response can only have one Content-Type
                $replace = (strcasecmp($name, "Content-Type") == 0);

                foreach ($values as $value) {

                    header(sprintf('%s: %s', $name, $value), $replace, $response->getStatusCode());

                }

            }

            header(sprintf('HTTP/%s %s %s', $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase()), true, $response->getStatusCode());

        }

        echo $response->getBody()->getContents();
    }
}
