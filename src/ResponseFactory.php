<?php
/**
 * Created by PhpStorm.
 * User: sylvain
 * Date: 08/07/2018
 * Time: 16:59
 */
namespace Lou117\Core;

use Psr\Http\Message\ResponseInterface;

class ResponseFactory
{
    const HTTP_HEADER_ALLOW = "Allow";


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
