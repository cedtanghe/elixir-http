<?php

namespace Elixir\HTTP;

use Psr\Http\Message\ServerRequestInterface as PSRServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class ServerRequestFactory
{
    /**
     * @param array $serverDataFailback
     *
     * @return array
     */
    public static function apacheRequestHeaders($serverDataFailback = null)
    {
        $headers = [];

        if (function_exists('apache_request_headers')) {
            foreach (apache_request_headers() as $key => $value) {
                $headers[$key][] = $value;
            }
        } elseif (null !== $serverDataFailback) {
            foreach ($serverDataFailback as $key => $value) {
                if (0 === strpos($key, 'HTTP_')) {
                    $name = strtr(substr($key, 5), '_', ' ');
                    $name = strtr(ucwords(strtolower($name)), ' ', '-');

                    $headers[$name][] = $value;
                } elseif (0 === strpos($key, 'CONTENT_')) {
                    $name = substr($key, 8);
                    $name = 'Content-'.(($name == 'MD5') ? $name : ucfirst(strtolower($name)));

                    $headers[$name][] = $value;
                }
            }
        }

        return $headers;
    }
    
    /**
     * @param array $server
     * @param array $query
     * @param array $body
     * @param array $cookie
     * @param array $files
     *
     * @return ServerRequest;
     */
    public static function createFromGlobals(array $server = null,
                                             array $query = null,
                                             array $body = null,
                                             array $cookie = null,
                                             array $files = null)
    {
        $server = $server ?: $_SERVER;
        $query = $query ?: $_GET;
        $body = $body ?: $_POST;
        $cookie = $cookie ?: $_COOKIE;
        $files = UploadedFile::create($files ?: $_FILES);
        $headers = static::apacheRequestHeaders($server);
        $URI = URI::createFromServer($server);

        return static::create(
            $URI,
            [
                'server' => $server,
                'query' => $query,
                'parsed_body' => $body,
                'cookie' => $cookie,
                'files' => $files,
                'headers' => $headers,
                'method' => isset($server['_method']) ? $server['_method'] : (isset($server['REQUEST_METHOD']) ? $server['REQUEST_METHOD'] : 'GET'),
                'body' => StreamFactory::create('php://input', ['mode' => 'r']),
            ]
        );
    }
    
    /**
     * @param PSRServerRequestInterface $request
     *
     * @return ServerRequestInterface
     */
    public static function convert(PSRServerRequestInterface $request)
    {
        return static::create($request->getUri(), [
            'server' => $request->getServerParams(),
            'query' => $request->getQueryParams(),
            'parsed_body' => $request->getParsedBody(),
            'cookie' => $request->getCookieParams(),
            'files' => $request->getUploadedFiles(),
            'headers' => $request->getHeaders(),
            'method' => $request->getMethod(),
            'body' => $request->getBody(),
            'attributes' => $request->getAttributes(),
            'protocol' => $request->getProtocolVersion(),
            'request_target' => $request->getRequestTarget(),
        ]);
    }

    /**
     * @param string|UriInterface|null $URI
     * @param array                    $config
     *
     * @return ServerRequest
     */
    public static function create($URI, array $config = [])
    {
        return new ServerRequest($URI, $config);
    }
}
