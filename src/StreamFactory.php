<?php

namespace Elixir\HTTP;

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
     * @throws \InvalidArgumentException
     */
    public static function create($stream = null, $mode = 'r', $content = null)
    {
        // Todo
        throw new \InvalidArgumentException('Invalid stream.');
    }
}
