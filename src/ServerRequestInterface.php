<?php

namespace Elixir\HTTP;

use Psr\Http\Message\ServerRequestInterface as PSRServerRequestInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

interface ServerRequestInterface extends PSRServerRequestInterface
{
    /**
     * @return boolean
     */
    public function isMainRequest();
    
    /**
     * @param self $request
     */
    public function setParentRequest(self $request);
    
    /**
     * @return self|null;
     */
    public function getParentRequest();
    
    /**
     * @return self;
     */
    public function getMainRequest();

    /**
     * @return string 
     */
    public function getBaseURL();
    
    /**
     * @return string 
     */
    public function getPathInfo();
    
    /**
     * @param array $attributes
     * @return self
     */
    public function withAttributes(array $attributes);
    
    /**
     * @return self
     */
    public function withoutAttributes();
}
