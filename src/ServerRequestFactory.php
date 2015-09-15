<?php

namespace Elixir\HTTP;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class ServerRequestFactory
{
    
    public static function create()
    {
        // Todo
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
                    $name = strtolower($name);
                    
                    $headers[$name][] = $value;
                } 
                else if (0 === strpos($key, 'CONTENT_'))
                { 
                    $name = substr($key, 8);
                    $name = 'Content-' . (($name == 'MD5') ? $name : ucfirst(strtolower($name)));
                    $name = strtolower($name);
                    
                    $headers[$name][] = $value;
                } 
            } 
        }
        
        return $headers;
    }
}
