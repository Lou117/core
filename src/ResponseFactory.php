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
use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory
{
    const HTTP_HEADER_ALLOW = "Allow";


    /**
     * Creates an HTML response (with Content-Type header set to "text/html").
     * @param string $body - Response body. If trim()-ed body is an empty string, neither Content-Type header nor body will be
     * added to returned response.
     * @param int $status - Response status (defaults to 200).
     * @return ResponseInterface
     */
    public static function createHtmlResponse(string $body, int $status = 200): ResponseInterface
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
    public static function createJsonResponse($body, int $status = 200): ResponseInterface
    {
        $encodedBody = json_encode($body);
        if ($encodedBody === null) {

            throw new InvalidArgumentException("Given body cannot be encoded to JSON");

        }

        $response = new Response($status);
        if (strlen($encodedBody) > 0) {

            $response = $response
                ->withHeader("Content-Type", "application/json")
                ->withBody(stream_for($encodedBody));

        }

        return $response;
    }

    /**
     *
     * @param $body - Response body. If trim()-ed body is an empty string, neither Content-Type header nor body will be
     * added to returned response.
     * @param int $status - Response status (defaults to 200).
     * @return ResponseInterface
     */
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
    public static function createTextResponse($body, int $status = 200): ResponseInterface
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
     *
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
     * Send the response to the client.
     *
     * Copied from Slim framework App::respond() method.
     * @param ResponseInterface $response
     */
    public static function sendToClient(ResponseInterface $response)
    {
        if (!headers_sent()) {

            // Headers
            foreach ($response->getHeaders() as $name => $values) {

                foreach ($values as $value) {

                    header(sprintf('%s: %s', $name, $value), false);

                }

            }

            /*
             * Set the status _after_ the headers, because of PHP's "helpful" behavior with location headers.
             * See https://github.com/slimphp/Slim/issues/1730
             */

            // Status
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

        }

        if (self::isEmptyResponse($response) === false) {

            $body = $response->getBody();
            if ($body->isSeekable()) {

                $body->rewind();

            }

            //$settings       = $this->container->get('settings');
            //$chunkSize      = $settings['responseChunkSize'];

            $contentLength  = $response->getHeaderLine('Content-Length');
            if (!$contentLength) {

                $contentLength = $body->getSize();

            }

            if (isset($contentLength)) {

                $amountToRead = $contentLength;
                while ($amountToRead > 0 && !$body->eof()) {

                    $data = $body->read(min(4096, $amountToRead));
                    echo $data;

                    $amountToRead -= strlen($data);
                    if (connection_status() != CONNECTION_NORMAL) {

                        break;

                    }

                }

            } else {

                while (!$body->eof()) {

                    echo $body->read(4096);

                    if (connection_status() != CONNECTION_NORMAL) {

                        break;

                    }

                }

            }
        }
    }
}
