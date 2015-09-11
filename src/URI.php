<?php

namespace Elixir\HTTP;

use Psr\Http\Message\UriInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class URI implements UriInterface
{
    /**
     * @param array $parts
     * @return string
     */
    public static function buildURI(array $parts = [])
    {
        $URI = '';
        
        // Scheme
        if (!empty($parts['scheme'])) 
        {
            $URI .= sprintf('%s://', $parts['scheme']);
        }
        
        // Authority
        if (!empty($parts['authority'])) 
        {
            $URI .= $parts['authority'];
        }
        
        // Path
        if (empty($parts['path']))
        {
            $path = '/';
        }
        else if ('/' !== substr($parts['path'], 0, 1)) 
        {
            $path = '/' . $parts['path'];
        }
        
        $URI .= $parts['path'];
        
        if (!empty($parts['query']))
        {
            $URI .= sprintf('?%s', $parts['query']);
        }
        
        if (!empty($parts['fragment']))
        {
            $URI .= sprintf('#%s', $parts['fragment']);
        }
        
        return $URI;
    }
    
    /**
     * @var string
     */
    protected $URI;
    
    /**
     * @var string
     */
    protected $scheme = '';
    
    /**
     * @var string
     */
    protected $userInfo = '';
    
    /**
     * @var string
     */
    protected $host = '';
    
    /**
     * @var int|null
     */
    protected $port;
    
    /**
     * @var string
     */
    protected $path = '';
    
    /**
     * @var string
     */
    protected $query = '';
    
    /**
     * @var string
     */
    protected $fragment = '';
    
    /**
     * @param string $URI
     * @throws \InvalidArgumentException
     */
    public function __construct($URI = '')
    {
        if (false === filter_var($URI, FILTER_VALIDATE_URL) || false === ($parts = parse_url($URI)))
        {
            throw new \InvalidArgumentException('This URI is invalid.');
        }
        
        // Scheme
        if (isset($parts['scheme']))
        {
            if (!$this->isValidScheme($parts['scheme']))
            {
                throw new \InvalidArgumentException(sprintf('Invalid or unsupported scheme "%s".', $parts['scheme']));
            }
            
            $this->scheme = $parts['scheme'];
        }
        
        // User info
        if (isset($parts['user']))
        {
            $this->userInfo = $parts['user'];
            
            if (isset($parts['pass']))
            {
                $this->userInfo .= ':' . $parts['pass'];
            }
        }
        
        // Host
        if (isset($parts['host']))
        {
            if (!$this->isValidHost($parts['host']))
            {
                throw new \InvalidArgumentException(sprintf('Invalid host "%s".', $parts['host']));
            }
            
            $this->host = $parts['host'];
        }
        
        // Port
        if (isset($parts['port']))
        {
            if (!$this->isValidPort($parts['port']))
            {
                throw new \InvalidArgumentException(sprintf('Invalid port "%s".', $parts['port']));
            }
            
            $this->port = $parts['port'];
        }
        
        // Path
        if (isset($parts['path']))
        {
            if (!$this->isValidPath($parts['path']))
            {
                throw new \InvalidArgumentException(sprintf('Invalid path "%s".', $parts['path']));
            }
            
            $this->path = $parts['path'];
        }
        
        // Query
        if (isset($parts['query']))
        {
            if (!$this->isValidQuery($parts['query']))
            {
                throw new \InvalidArgumentException(sprintf('Invalid invalid query string "%s".', $parts['query']));
            }
            
            $this->query = $parts['query'];
        }
        
        // Fragment
        if (isset($parts['fragment']))
        {
            $this->fragment = $parts['fragment'];
        }
        
        $this->URI = $URI;
    }
    
    /**
     * @return string
     */
    public function getURI()
    {
        if (null !== $this->URI)
        {
            return $this->URI;
        }
        
        $this->URI = static::buildURI([
            'scheme' => $this->scheme,
            'authority' => $this->getAuthority(),
            'path' => $this->path,
            'query' => $this->query,
            'fragment' => $this->fragment
        ]);
        
        return $this->URI;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getScheme()
    {
        return $this->scheme;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAuthority()
    {
        if (empty($this->host))
        {
            return '';
        }
        
        $authority = '';
        
        if ($this->userInfo)
        {
            $authority .= $this->userInfo . '@';
        }
        
        $authority .= $this->host;
        
        if (!in_array($this->scheme, ['http', 'https']) || !in_array($this->port, [80, 443]))
        {
            $authority .= ':' . $this->port;
        }
        
        return $authority;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHost()
    {
        return strtolower($this->host);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        if (!in_array($this->scheme, ['http', 'https']) || !in_array($this->port, [80, 443]))
        {
            return $this->port;
        }
        
        return null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return $this->query;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFragment()
    {
        return $this->fragment;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withScheme($scheme)
    {
        if (!$this->isValidScheme($scheme))
        {
            throw new \InvalidArgumentException(sprintf('Invalid or unsupported scheme "%s".', $scheme));
        }
        
        $new = clone $this;
        $new->scheme = $scheme;
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withUserInfo($user, $password = null)
    {
        $new = clone $this;
        $new->userInfo = $user;
            
        if (null !== $password)
        {
            $new->userInfo .= ':' . $password;
        }
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withHost($host)
    {
        if (!$this->isValidHost($host))
        {
            throw new \InvalidArgumentException(sprintf('Invalid host "%s".', $host));
        }
        
        $new = clone $this;
        $new->host = $host;
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withPort($port)
    {
        if (!$this->isValidPort($port))
        {
            throw new \InvalidArgumentException(sprintf('Invalid port "%s".', $port));
        }
        
        $new = clone $this;
        $new->port = $port;
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withPath($path)
    {
        if (!$this->isValidPath($path))
        {
            throw new \InvalidArgumentException(sprintf('Invalid path "%s".', $path));
        }
        
        $new = clone $this;
        $new->path = $path;
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withQuery($query)
    {
        if (strpos($query, '?') === 0)
        {
            $query = substr($query, 1);
        }
        
        if (!$this->isValidQuery($query))
        {
            throw new \InvalidArgumentException(sprintf('Invalid invalid query string "%s".', $query));
        }
        
        $new = clone $this;
        $new->query = $query;
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withFragment($fragment)
    {
        if (strpos($fragment, '#') === 0)
        {
            $fragment = substr($fragment, 1);
        }
        
        $new = clone $this;
        $new->fragment = $fragment;
        
        return $new;
    }
    
    /**
     * @param string $scheme
     * @return boolean
     */
    public function isValidScheme($scheme)
    {
        return true;
    }
    
    /**
     * @param string $host
     * @return boolean
     */
    public function isValidHost($host)
    {
        return true;
    }
    
    /**
     * @param int $port
     * @return boolean
     */
    public function isValidPort($port)
    {
        // Port 0 is reserved.
        return $port < 1 || $port > 65535;
    }
    
    /**
     * @param string $path
     * @return boolean
     */
    public function isValidPath($path)
    {
        return false !== strpos($path, '#') && false !== strpos($path, '?');
    }
    
    /**
     * @param string $query
     * @return boolean
     */
    public function isValidQuery($query)
    {
        return false !== strpos($query, '#');
    }
    
    /**
     * @param string $string
     * @return string
     */
    public static function encode($string)
    {
        return rawurlencode($string);
    }
    
    /**
     * @param string $string
     * @return string
     */
    public static function decode($string)
    {
        return rawurldecode($string);
    }
    
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getURI();
    }

    /**
     * @ignore
     */
    public function __clone() 
    {
        $this->URI = null;
    }
}
