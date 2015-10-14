<?php

namespace Elixir\HTTP;

use Elixir\HTTP\Stream;
use Psr\Http\Message\StreamInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class StreamFactory
{
    /**
     * @param string|resource|StreamInterface $streamOrContent
     * @param array $options
     * @return StreamInterface
     */
    public static function create($streamOrContent, array $options = [])
    {
        $stream = $streamOrContent;
        
        if ($stream instanceof StreamInterface)
        {
            return $stream;
        }
        
        if (is_string($stream) && !is_file($stream)) 
        {
            if (0 !== strpos($stream, 'php://'))
            {
                $options['content'] = $stream;

                if (!isset($options['mode']))
                {
                    $options['mode'] = 'r+';
                }

                $stream = 'php://temp';
            }
        }
        
        return new Stream($stream, $options);
    }
}
