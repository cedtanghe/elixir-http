<?php

namespace Elixir\HTTP;

use Psr\Http\Message\ResponseInterface as PSRResponseInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

interface ResponseInterface extends PSRResponseInterface
{
    /**
     * @return string
     */
    public function getCharset();
    
    /**
     * @param string $charset
     * @return self
     */
    public function withCharset($charset);
    
    /**
     * @return boolean
     */
    public function send();
}
