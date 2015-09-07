<?php

namespace Elixir\HTTP;

use Elixir\HTTP\Stream;
use Elixir\HTTP\URI;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class Request implements RequestInterface
{
    /**
     * @var UriInterface;
     */
    protected $URI;
    
    /**
     * @var string 
     */
    protected $method;
    
    /**
     * @var StreamInterface 
     */
    protected $body;
    
    /**
     * @var array
     */
    protected $headers = [];
    
    /**
     * @var string
     */
    protected $requestTarget;
    
    /**
     * @var string
     */
    protected $protocol = '1.1';

    /**
     * @param string|URI|null $URI
     * @param string|null $method
     * @param string|resource|StreamInterface $body
     * @param array $headers
     * @throws \InvalidArgumentException
     */
    public function __construct($URI = null, $method = null, $body = 'php://temp', array $headers = [])
    {
        if (is_string($uri)) 
        {
            $uri = new URI($uri);
        }
        
        $this->uri = $uri ?: new URI();
        
        if($method)
        {
            if (!$this->isValidMethod($method))
            {
                throw new \InvalidArgumentException('Unsupported HTTP method');
            }
            
            $this->method = $method;
        }
        else
        {
            $this->method = 'GET';
        }
        
        $this->body = ($body instanceof StreamInterface) ? $body : new Stream();
        
        // Todo prepare headers
        $this->headers = $headers;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRequestTarget()
    {
        if (null !== $this->requestTarget)
        {
            return $this->requestTarget;
        }
        
        $target = $this->URI->getPath();
        $query = $this->URI->getQuery();
        
        if ($query)
        {
            $target .= '?' . $query;
        }
        
        return empty($target) ? '/' : $target;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withRequestTarget($requestTarget)
    {
        $new = clone $this;
        $new->requestTarget = $requestTarget;
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->method;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withMethod($method)
    {
        if (!$this->isValidMethod($method))
        {
            throw new \InvalidArgumentException('Unsupported HTTP method');
        }
        
        $new = clone $this;
        $new->method = $method;
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUri()
    {
        return $this->URI;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withUri(UriInterface $URI, $preserveHost = false)
    {
        $new = clone $this;
        $new->URI = $URI;
        
        if (($preserveHost && $this->hasHeader('Host')) || !$URI->getHost())
        {
            return $new;
        }
        
        $host = $URI->getHost();
        $port = $URI->getPort();
        
        if ($port) 
        {
            $host .= ':' . $port;
        }
        
        $new->headers['Host'] = [$host];
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion()
    {
        return $this->protocol;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withProtocolVersion($version)
    {
        $new = clone $this;
        $new->protocol = $version;
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    
    /**
     * {@inheritdoc}
     */
    public function hasHeader($name)
    {
        return array_key_exists(
            strtolower($name), 
            array_map(
                function($header)
                {
                    return strtolower($header);
                },
                array_keys($this->headers)
            )
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHeader($name)
    {
        foreach ($this->headers as $key => $value)
        {
            if (strtolower($key) == strtolower($name))
            {
                return (array)$value;
            }
        }
        
        return [];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name)
    {
        return implode(',', $this->getHeader($name));
    }
    
    /**
     * {@inheritdoc}
     */
    public function withHeader($name, $value)
    {
        if (!$this->isValidHeaderValue($value))
        {
            throw new \InvalidArgumentException('Invalid header value');
        }
        
        $new = clone $this;
        
        foreach ($new->headers as $key => $value)
        {
            if (strtolower($key) == strtolower($name))
            {
                unset($new->headers[$key]);
                break;
            }
        }
        
        $new->headers[$name] = (array)$value;
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withAddedHeader($name, $value)
    {
        if (!$this->isValidHeaderValue($value))
        {
            throw new \InvalidArgumentException('Invalid header value');
        }
        
        $new = clone $this;
        
        if (isset($new->headers[$name]) && false !== array_search($value, $new->headers[$name]))
        {
            return $new;
        }
        
        $new->headers[$name][] = $value;
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withoutHeader($name)
    {
        $new = clone $this;
        
        foreach ($new->headers as $key => $value)
        {
            if (strtolower($key) == strtolower($name))
            {
                unset($new->headers[$key]);
                break;
            }
        }
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body)
    {
        if (!$this->isValidBody($body))
        {
            throw new \InvalidArgumentException('Invalid stream');
        }
        
        $new = clone $this;
        $new->body = $body;
        
        return $new;
    }
    
    /**
     * @param string $method
     * @return boolean
     */
    protected function isValidMethod($method)
    {
        return in_array($method, ['HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE']);
    }
    
    /**
     * @param string $value
     * @return boolean
     */
    protected function isValidHeaderValue($value)
    {
        if (is_array($value))
        {
            foreach ($value as $v)
            {
                if (!is_string($v))
                {
                    return false;
                }
            }
            
            return true;
        }
        
        return is_string($value);
    }
    
    /**
     * @param StreamInterface $body
     * @return boolean
     */
    protected function isValidBody(StreamInterface $body)
    {
        return true;
    }
}
