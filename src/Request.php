<?php

namespace Elixir\HTTP;

use Elixir\HTTP\MessageTrait;
use Elixir\HTTP\StreamFactory;
use Elixir\HTTP\URI;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class Request implements RequestInterface
{
    use MessageTrait;
    
    /**
     * @var UriInterface;
     */
    protected $URI;
    
    /**
     * @var string 
     */
    protected $method = '';
    
    /**
     * @var string
     */
    protected $requestTarget;
    
    /**
     * @param string|UriInterface|null $URI
     * @param array $config
     * @throws \InvalidArgumentException
     */
    public function __construct($URI = null, array $config = [])
    {
        $config += ['body' => 'php://temp'];
        
        // URI
        if (is_string($URI))
        {
            $URI = new URI($URI);
        }
        
        $this->URI = $URI ?: new URI();
        
        // Method
        if (!empty($config['method']))
        {
            if (!$this->isValidMethod($config['method']))
            {
                throw new \InvalidArgumentException('Unsupported HTTP method.');
            }

            $this->method = $config['method'];
        }
        
        // Body
        $this->body = ($config['body'] instanceof StreamInterface) ? $config['body'] : StreamFactory::create($config['body']);
        
        // Header
        if (!empty($config['headers']))
        {
            foreach ($config['headers'] as $header => $values)
            {
                foreach ($values as $value)
                {
                    if (!$this->isValidHeaderValue($value))
                    {
                        throw new \InvalidArgumentException(sprintf('Invalid header value for "%s".', $header));
                    }
                }
            }
            
            $this->headers = $config['headers'];
        }
        
        // Protocol
        if (!empty($config['protocol']))
        {
            $this->protocol = $config['protocol'];
        }
        
        // Request target
        if (!isset($config['request_target']))
        {
            $this->requestTarget = $config['request_target'];
        }
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
            throw new \InvalidArgumentException('Unsupported HTTP method.');
        }
        
        $new = clone $this;
        $new->method = $method;
        
        return $new;
    }
    
    /**
     * @return boolean 
     */
    public function isHead()
    {
        // Todo
    }
    
    /**
     * @return boolean 
     */
    public function isQuery()
    {
        // Todo
    }
    
    /**
     * @return boolean 
     */
    public function isPost()
    {
        // Todo
    }
    
    /**
     * @return boolean 
     */
    public function isPut()
    {
        // Todo
    }
    
    /**
     * @return boolean 
     */
    public function isDelete()
    {
        // Todo
    }
    
    /**
     * @return boolean 
     */
    public function isConnect()
    {
        // Todo
    }
    
    /**
     * @return boolean 
     */
    public function isOptions()
    {
        // Todo
    }
    
    /**
     * @return boolean 
     */
    public function isTrace()
    {
        // Todo
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
     * @param string $method
     * @return boolean
     */
    public function isValidMethod($method)
    {
        return in_array($method, ['HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE']);
    }
}
