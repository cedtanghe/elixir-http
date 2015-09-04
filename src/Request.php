<?php

namespace Elixir\HTTP;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class Request implements RequestInterface
{
    public function __construct($uri = null, $method = null, $body = 'php://memory', array $headers = [])
    {
        
    }
    
    public function getRequestTarget();
    public function withRequestTarget($requestTarget);
    public function getMethod();
    public function withMethod($method);
    public function getUri();
    public function withUri(UriInterface $uri, $preserveHost = false);
    public function getProtocolVersion();
    public function withProtocolVersion($version);
    public function getHeaders();
    public function hasHeader($name);
    public function getHeader($name);
    public function getHeaderLine($name);
    public function withHeader($name, $value);
    public function withAddedHeader($name, $value);
    public function withoutHeader($name);
    public function getBody();
    public function withBody(StreamInterface $body);
}
