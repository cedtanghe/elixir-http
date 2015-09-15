<?php

namespace Elixir\HTTP;

use Elixir\HTTP\Cookie;
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
     * @var array 
     */
    protected $cookies = [];
    
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
     * @param Cookie|array $cookie
     * @return self
     */
    public function withCookies($cookie)
    {
        $lines = [];
        
        if (is_array($cookie))
        {
            foreach ($cookie as $c)
            {
                $lines = array_merge($lines, (array)$cookie->formatToString());
            }
        }
        else
        {
            $lines = $cookie->formatToString();
        }
        
        return $this->withHeader('Set-Cookie', $lines);
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
            }
        }
        
        $new->headers[$name] = (array)$value;
        
        if (strtolower($name)== 'set-cookie')
        {
            $cookies = [];
            
            foreach((array)$value as $cookie)
            {
                $cookies[] = Cookie::fromString($cookie);
            }
            
            $new->cookies = $cookies;
        }
        
        return $new;
    }
    
    /**
     * @param Cookie|array $cookie
     * @return self
     */
    public function withAddedCookie($cookie)
    {
        $lines = [];
        
        if (is_array($cookie))
        {
            foreach ($cookie as $c)
            {
                $lines = array_merge($lines, (array)$cookie->formatToString());
            }
        }
        else
        {
            $lines = $cookie->formatToString();
        }
        
        return $this->withAddedHeader('Set-Cookie', $lines);
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
        $headers = [];
        
        foreach ($new->headers as $key => $value)
        {
            if (strtolower($key) == strtolower($name))
            {
                $headers = array_merge($headers, $new->headers[$key]);
                unset($new->headers[$key]);
            }
        }
        
        $new->headers[$name] = array_merge($headers, (array)$value);
        
        if (strtolower($name)== 'set-cookie')
        {
            foreach((array)$value as $cookie)
            {
                $new->cookies[] = Cookie::fromString($cookie);
            }
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
        
        if (strtolower($name) == 'set-cookie')
        {
            $this->cookies = [];
        }
        
        return $new;
    }
    
    /**
     * @return array
     */
    public function getCookies()
    {
        return $this->cookies;
    }
    
    /**
     * @return array
     */
    protected function parseCacheControl()
    {
        if (!$this->hasHeader('Cache-Control') && !$this->hasHeader('Etag') && !$this->hasHeader('Last-Modified') && !$this->hasHeader('Expires'))
        {
            return ['no-cache'];
        }
        else if (!$this->hasHeader('Cache-Control'))
        {
            return ['private, must-revalidate'];
        }
        
        $cacheControl = [];

        foreach ($this->headers as $key => $value)
        {
            if (strtolower($key) == 'cache-control')
            {
                $cacheControl = $this->headers[$key];
                break;
            }
        }

        ksort($cacheControl);
        $private = true;

        foreach ($cacheControl as $value)
        {
            if (false !== strpos($value, 'public') || false !== strpos($value, 'private') || false !== strpos($value, 's-maxage'))
            {
                $private = false;
                break;
            }
        }

        $line = implode(', ', $cacheControl);

        if ($private)
        {
            $line .= ', private';
        }

        return [$line];
    }
    
    /**
     * @return array
     */
    public function getSortedHeaders()
    {
        $this->headers['Cache-Control'] = $this->parseCacheControl();
        unset($this->headers['cache-control']);
        
        $headers = array_slice($this->headers, 0);
        $headers['Cache-Control'] = $this->parseCacheControl();
        ksort($headers);
        
        $sorted = [];
        
        foreach($headers as $key => $values)
        {
            if(preg_match('/^HTTP\/1\.(0|1) \d{3}.*$/', $key))
            {
                array_unshift($sorted, $key);
            }
            else
            {
                foreach($values as $value)
                {
                    $headers[] = sprintf('%s : %s', $key, $value);
                }
            }
        }
        
        return $headers;
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
