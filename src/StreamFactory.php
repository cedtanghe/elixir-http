<?php

namespace Elixir\HTTP;

use Psr\Http\Message\StreamInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class StreamFactory
{
    /**
     * @param string|resource|StreamInterface $body
     * @return StreamInterface
     */
    public static function create($body = null)
    {
        // Todo
        return null;
    }
}
