<?php declare(strict_types=1);
namespace Lou117\Core;

use JsonException;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory
{
    const HTTP_HEADER_ALLOW = "Allow";
    const HTTP_HEADER_LOCATION = "Location";
    const HTTP_HEADER_CONTENT_TYPE = "Content-Type";
    const HTTP_HEADER_CONTENT_LENGTH = "Content-Length";


    /**
     * Creates an HTML response (with `Content-Type` header set to `text/html`).
     * @param string $body - response body. If `trim()`-ed body is an empty string, neither `Content-Type` header nor
     * body will be added to returned response.
     * @param int $status - response status (defaults to `200`).
     * @return ResponseInterface
     */
    public static function createHTMLResponse(string $body, int $status = 200): ResponseInterface
    {
        $response = new Response($status);

        if (strlen(trim($body)) > 0) {
            $response = $response
                ->withHeader(self::HTTP_HEADER_CONTENT_TYPE, "text/html")
                ->withBody(Utils::streamFor($body));
        }

        return $response;
    }

    /**
     * Creates a JSON response (with `Content-Type` header set to `application/json`).
     * @param mixed $body - response body to be encoded to JSON. If JSON encoding results in an empty string, neither
     * `Content-Type` header nor body will be added to returned response.
     * @param int $status - response status (defaults to 200).
     * @return ResponseInterface
     * @throws JsonException
     */
    public static function createJSONResponse(mixed $body, int $status = 200): ResponseInterface
    {
        $encodedBody = json_encode($body, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);

        $response = new Response($status);

        if (strlen($encodedBody) > 0) {
            $response = $response
                ->withHeader(self::HTTP_HEADER_CONTENT_TYPE, "application/json; charset=utf-8")
                ->withBody(Utils::streamFor($encodedBody));
        }

        return $response;
    }

    /**
     * Creates a redirect response, with status set to 302 and `Location` header set to given `$location`.
     * @param string $location - value for `Location` header.
     * @return ResponseInterface
     * @throws InvalidArgumentException - when given `$location` is an empty string.
     */
    public static function createRedirectResponse(string $location): ResponseInterface
    {
        if (empty($location)) {
            throw new InvalidArgumentException("Location header value cannot be empty");
        }

        return (new Response(302))->withHeader(self::HTTP_HEADER_LOCATION, $location);
    }

    /**
     * Creates a text response (with `Content-Type` header set to `text/plain`).
     * @param $body - response body. If `trim()`-ed body is an empty string, neither `Content-Type` header nor body will
     * be added to returned response.
     * @param int $status - response status (defaults to 200).
     * @return ResponseInterface
     */
    public static function createTextResponse(string $body, int $status = 200): ResponseInterface
    {
        $response = new Response($status);

        if (strlen(trim($body)) > 0) {
            $response = $response
                ->withHeader(self::HTTP_HEADER_CONTENT_TYPE, "text/plain")
                ->withBody(Utils::streamFor($body));
        }

        return $response;
    }

    /**
     * Returns `true` if the provided response must not output a body and `false` if the response could have a body.
     * @see https://tools.ietf.org/html/rfc7231
     * @param ResponseInterface $response
     * @return bool
     */
    public static function isEmptyResponse(ResponseInterface $response)
    {
        if (method_exists($response, "isEmpty")) {
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
                $replace = false;

                // Skip Content-Length header, to ensure it is sent with correct body length, if any.
                if (strcasecmp($name, self::HTTP_HEADER_CONTENT_LENGTH) === 0) {
                    continue;
                }

                if (strcasecmp($name, self::HTTP_HEADER_CONTENT_TYPE) === 0) {
                    // A response can only have one Content-Type header
                    $replace = true;

                    // Content-Type header is sent only if response is not supposed to be empty
                    if (self::isEmptyResponse($response)) {
                        continue;
                    }
                }

                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), $replace, $response->getStatusCode());
                }
            }

            header(sprintf('HTTP/%s %s %s', $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase()), true, $response->getStatusCode());

            if (
                $response->getBody()->getSize() > 0
                && !self::isEmptyResponse($response)
            ) {
                header(self::HTTP_HEADER_CONTENT_LENGTH.': '.$response->getBody()->getSize(), true, $response->getStatusCode());
            }
        }

        echo $response->getBody()->getContents();
    }
}
