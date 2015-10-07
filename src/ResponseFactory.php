<?php

namespace Elixir\HTTP;

use Elixir\HTTP\Response;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class ResponseFactory
{
    /**
     * @param string $string
     * @return Response
     * @throws \InvalidArgumentException
     */
    public static function fromString($string)
    {
        $lines = explode("\r\n", $string);
        $line = array_shift($lines);
        
        if(!$line || !preg_match('/^(?P<protocol>HTTP\/1\.(0|1)) (?P<status>\d{3}).*$/', $line, $matches))
        {
            throw new \InvalidArgumentException('Response text is not valid.');
        }
        
        $protocol = $matches['protocol'];
        $status = $matches['status'];
        $headers = [];
        $content = [];
        $type = 'header';
        
        while(count($lines) > 0)
        {
            $line = array_shift($lines);
            
            if($type === 'header' && empty($line))
            {
                $type = 'content';
                continue;
            }
            
            if ($type === 'header')
            {
                list($name, $value) = array_map('trim', explode(':', $line));
                $headers[$name][] = $value;
            }
            else
            {
                $content[] = $line;
            }
        }
        
        return new Response([
            'body_content' => implode("\r\n", $content),
            'status_code' => $status,
            'protocol' => $protocol,
            'headers' => $headers
        ]);
    }
}
