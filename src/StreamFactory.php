<?php

namespace Elixir\HTTP;

use Elixir\HTTP\PhpInputStream;
use Elixir\HTTP\Stream;
use Psr\Http\Message\StreamInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class StreamFactory
{
    /**
     * @param string|resource|StreamInterface $stream
     * @param string $mode
     * @param string $content
     * @return StreamInterface
     */
    public static function create($stream, $mode = 'r', $content = null)
    {
        if ($stream === 'php://input')
        {
            $content = null;
            $stream = new PhpInputStream($stream, 'r');
        }
        else if (!$stream instanceof StreamInterface)
        {
            $stream = new Stream($stream, $mode);
        }
        
        if (null !== $content)
        {
            $stream->write($content);
        }
        
        return $stream;
    }
}
