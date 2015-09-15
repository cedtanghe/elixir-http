<?php

namespace Elixir\HTTP;

use Psr\Http\Message\StreamInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

trait MessageTrait
{
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
    protected $protocol = '1.1';
    
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
            throw new \InvalidArgumentException(sprintf('Invalid header value for "%s".', $name));
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
            throw new \InvalidArgumentException(sprintf('Invalid header value for "%s".', $name));
        }
        
        $new = clone $this;
        
        if (!isset($new->headers[$name]) || false === array_search($value, $new->headers[$name]))
        {
            $new->headers[$name][] = $value;
        }
        
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
            throw new \InvalidArgumentException('Invalid stream.');
        }
        
        $new = clone $this;
        $new->body = $body;
        
        return $new;
    }
    
    /**
     * @param string $value
     * @return boolean
     */
    public function isValidHeaderValue($value)
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
    public function isValidBody(StreamInterface $body)
    {
        return true;
    }
}
