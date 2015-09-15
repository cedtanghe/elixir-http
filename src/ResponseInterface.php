<?php

namespace Elixir\HTTP;

use Psr\Http\Message\ResponseInterface as PSRResponseInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

interface ResponseInterface extends PSRResponseInterface
{
    /**
     * @return boolean
     */
    public function send();
}
