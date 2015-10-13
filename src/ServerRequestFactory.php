<?php

namespace Elixir\HTTP;

use Elixir\HTTP\ServerRequest;
use Elixir\HTTP\UploadedFile;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class ServerRequestFactory
{
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
        
        return new ServerRequest(
            $URI, 
            [
                'server' => $server,
                'query' => $query,
                'parsed_body' => $body,
                'cookie' => $cookie,
                'files' => $files,
                'headers' => $headers,
                'method' => isset($server['_method']) ? $server['_method'] : (isset($server['REQUEST_METHOD']) ? $server['REQUEST_METHOD'] : 'GET'),
                'body' => StreamFactory::create('php://input', 'r')
            ]
        );
    }
    
    /**
     * @param array $serverDataFailback
     * @return array
     */
    public static function apacheRequestHeaders($serverDataFailback = null)
    {
        $headers = [];
        
        if(function_exists('apache_request_headers'))
        {
            foreach(apache_request_headers() as $key => $value) 
            {
                $headers[$key][] = $value;
            }
        }
        else if(null !== $serverDataFailback)
        {
            foreach($serverDataFailback as $key => $value) 
            { 
                if (0 === strpos($key, 'HTTP_'))
                { 
                    $name = strtr(substr($key, 5), '_', ' ');
                    $name = strtr(ucwords(strtolower($name)), ' ', '-');
                    
                    $headers[$name][] = $value;
                } 
                else if (0 === strpos($key, 'CONTENT_'))
                { 
                    $name = substr($key, 8);
                    $name = 'Content-' . (($name == 'MD5') ? $name : ucfirst(strtolower($name)));
                    
                    $headers[$name][] = $value;
                } 
            } 
        }
        
        return $headers;
    }
}
