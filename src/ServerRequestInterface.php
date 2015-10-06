<?php

namespace Elixir\HTTP;

use Psr\Http\Message\RequestInterface as PSRRequestInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

interface ServerRequestInterface extends PSRRequestInterface
{
    /**
     * @return string 
     */
    public function getBaseURL();
    
    /**
     * @return string 
     */
    public function getPathInfo();
}
