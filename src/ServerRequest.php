<?php

namespace Elixir\HTTP;

use Elixir\HTTP\Request;
use Elixir\HTTP\ServerRequestInterface;
use Elixir\STDLib\ArrayUtils;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @var array
     */
    protected $serverParams = [];
    
    /**
     * @var array
     */
    protected $cookieParams = [];
    
    /**
     * @var array
     */
    protected $queryParams = [];
    
    /**
     * @var array
     */
    protected $uploadedFiles = [];
    
    /**
     * @var null|array|object
     */
    protected $parsedBody;
    
    /**
     * @var array
     */
    protected $attributes = [];
    
    /**
     * @var boolean
     */
    protected $fromURI = false;
    
    /**
     * @var string|null
     */
    protected $baseURL;
    
    /**
     * @param string|UriInterface|null $URI
     * @param array $config
     * @throws \InvalidArgumentException
     */
    public function __construct($URI = null, array $config = [])
    {
        // Body
        if (!isset($config['body']))
        {
            $config['body'] = StreamFactory::create('php://input', ['mode' => 'r']);
        }
        
        parent::__construct($URI, $config);
        
        // Server params
        if (!empty($config['server']))
        {
            $this->serverParams = (array)$config['server'];
        }
        
        // Cookie params
        if (!empty($config['cookie']))
        {
            $this->cookieParams = (array)$config['cookie'];
        }
        
        // Query params
        if (!empty($config['query']))
        {
            $this->queryParams = (array)$config['query'];
        }
        
        if (!empty($this->URI->getQuery()))
        {
            parse_str($this->URI->getQuery(), $this->queryParams);
        }
        
        // Uploaded files
        if (!empty($config['files']))
        {
            if (!$this->isValidUploadedFiles($config['files']))
            {
                throw new \InvalidArgumentException('Invalid structure is provided in uploaded files.');
            }

            $this->uploadedFiles = $config['files'];
        }
        
        // Parsed body
        if (isset($config['parsed_body']))
        {
            if (!$this->isValidParsedBody($config['parsed_body']))
            {
                throw new \InvalidArgumentException('Unsupported argument type is provided for parsed body.');
            }
            
            $this->parsedBody = $config['parsed_body'];
        }
        
        // Attributes
        if (!empty($config['attributes']))
        {
            $this->attributes = (array)$config['attributes'];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function prepareURI($URI = null)
    {
        if (is_string($URI))
        {
            $URI = new URI($URI);
        }
        
        return $URI ?: URI::createFromServer($this->serverParams);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }
    
    /**
     * @param mixed $key
     * @return boolean
     */
    public function hasServerParam($key)
    {
        return ArrayUtils::has($key, $this->serverParams);
    }
    
    /**
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function getServerParam($key, $default = null)
    {
        return ArrayUtils::get($key, $this->serverParams, $default);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }
    
    /**
     * @param mixed $key
     * @return boolean
     */
    public function hasCookieParam($key)
    {
        return ArrayUtils::has($key, $this->cookieParams);
    }
    
    /**
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function getCookieParam($key, $default = null)
    {
        return ArrayUtils::get($key, $this->cookieParams, $default);
    }
    
    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->cookieParams = $cookies;
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }
    
    /**
     * @param mixed $key
     * @return boolean
     */
    public function hasQueryParam($key)
    {
        return ArrayUtils::has($key, $this->queryParams);
    }
    
    /**
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function getQueryParam($key, $default = null)
    {
        return ArrayUtils::get($key, $this->queryParams, $default);
    }
    
    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query)
    {
        $new = clone $this;
        $new->queryParams = $query;
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }
    
    /**
     * @param string $key
     * @return boolean
     */
    public function hasUploadedFile($key)
    {
        return ArrayUtils::has($key, $this->uploadedFiles);
    }
    
    /**
     * @param string $key
     * @return UploadedFileInterface|null
     */
    public function getUploadedFile($key)
    {
        return ArrayUtils::get($key, $this->queryParams, null);
    }
    
    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        if (!$this->isValidUploadedFiles($uploadedFiles))
        {
            throw new \InvalidArgumentException('Invalid structure is provided in uploaded files.');
        }
        
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }
    
    /**
     * @param mixed $key
     * @return boolean
     */
    public function hasPostParam($key)
    {
        return ArrayUtils::has($key, $this->parsedBody ?: []);
    }
    
    /**
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function getPostParam($key, $default = null)
    {
        return ArrayUtils::get($key, $this->parsedBody ?: [], $default);
    }
    
    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data)
    {
        if (!$this->isValidParsedBody($data))
        {
            throw new \InvalidArgumentException('Unsupported argument type is  provided for parsed body.');
        }
        
        $new = clone $this;
        $new->parsedBody = $data;
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
    
    /**
     * @param mixed $key
     * @return boolean
     */
    public function hasAttribute($key)
    {
        return ArrayUtils::has($key, $this->attributes);
    }
    
    /**
     * {@inheritdoc}
     * @param mixed $key
     */
    public function getAttribute($key, $default = null)
    {
        return ArrayUtils::get($key, $this->attributes, $default);
    }
    
    /**
     * {@inheritdoc}
     */
    public function withAttribute($key, $value)
    {
        $new = clone $this;
        ArrayUtils::set($key, $value, $new->attributes);
        
        return $new;
    }
    
    /**
     * {@inheritdoc}
     */
    public function withoutAttribute($key)
    {
        if (ArrayUtils::has($key, $this->attributes)) 
        {
            return clone $this;
        }
        
        $new = clone $this;
        ArrayUtils::remove($key, $this->attributes);
        
        return $new;
    }
    
    /**
     * @return self
     */
    public function fromURI()
    {
        $this->fromURI = true;
        return $this;
    }
    
    /**
     * @return self
     */
    public function fromHeaders()
    {
        $this->fromURI = false;
        return $this;
    }
    
    /**
     * @param string $value 
     * @throws \InvalidArgumentException
     */
    public function setBaseURL($value)
    {
        $value = rtrim($value, '/');
        
        if (0 !== strpos((string)$this->URI, $value))
        {
            throw new \InvalidArgumentException('The base url must be included in the overall uri.');
        }
        
        $this->baseURL = $value;
    }

    /**
     * @return string 
     */
    public function getBaseURL()
    {
        if ($this->fromURI)
        {
            $this->fromURI = false;
            
            return URI::buildURIString([
                'scheme' => $this->URI->getScheme(),
                'authority' => $this->URI->getAuthority()
            ]);
        }
        else
        {
            if(null === $this->baseURL)
            {
                if($this->getServerParam('SCRIPT_NAME')) 
                {
                    $base = dirname($this->getServerParam('SCRIPT_NAME'));
                } 
                else if($this->getServerParam('PHP_SELF')) 
                {
                    $base = dirname($this->getServerParam('PHP_SELF'));
                }
                else
                {
                    $base = '';
                }

                if(!empty($base))
                {
                    $requestURI = $this->getServerParam('REQUEST_URI');
                    $qpos = strpos($requestURI, '?');

                    if (false !== $qpos) 
                    {
                        $requestURI = substr($requestURI, 0, $qpos);
                    }

                    if(false === strpos($requestURI, $base))
                    {
                        // Using mod_rewrite ?
                        $segments = explode('/', trim($base, '/'));

                        do 
                        {
                            array_pop($segments);
                            $base = '/' . implode('/', $segments);
                        } 
                        while(count($segments) > 0 && false === strpos($requestURI, $base));
                    }
                }

                $this->setBaseURL(sprintf('%s://', $this->isSecure() ? 'https' : 'http') . $this->getServerParam('HTTP_HOST', '') . $base);
            }
        }
        
        return $this->baseURL;
    }
    
    /**
     * @return string 
     */
    public function getPathInfo()
    {
        if ($this->fromURI)
        {
            $this->fromURI = false;
            
            $pathInfo = str_replace($this->getBaseURL(), '', (string)$this->URI);
            $qpos = strpos($pathInfo, '?');

            if (false !== $qpos) 
            {
                $pathInfo = substr($pathInfo, 0, $qpos);
            }

            return '/' . ltrim($pathInfo, '/');
        }
        else
        {
            return $this->URI->getPath();
        }
    }

    /**
     * @return boolean 
     */
    public function isSecure()
    {
        if ($this->fromURI)
        {
            $this->fromURI = false;
            return $this->URI->getScheme() === 'https';
        }
        else
        {
            $HTTPS = $this->getServerParam('HTTPS');

            if ($HTTPS && $HTTPS !== 'off')
            {
                return true;
            }

            return $this->getServerParam('HTTP_X_FORWARDED_PROTO') === 'https';
        }
    }
    
    /**
     * @return string|null
     */
    public function getIP()
    {
        $validateIP = function($ip)
        {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) 
            {
                return false;
            }
            
            return true;
        };
        
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR', 
            'HTTP_X_FORWARDED', 
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED'
        ];
        
        foreach ($keys as $key) 
        {
            $value = $this->getServerParam($key);
            
            if (null !== $value) 
            {
                foreach (explode(',', $value) as $ip)
                {
                    $ip = trim($ip);
                    
                    if ($validateIP($ip))
                    {
                        return $ip;
                    }
                }
            }
        }
        
        return $this->getServerParam('REMOTE_ADDR');
    }
    
    /**
     * @return boolean 
     */
    public function isAjax()
    {
        return strtoupper($this->getServerParam('HTTP_X_REQUESTED_WITH', '')) === 'XMLHTTPREQUEST';
    }
    
    /**
     * @return string|null 
     */
    public function getUser()
    {
        if ($this->fromURI)
        {
            $this->fromURI = false;
            
            $userInfos = $this->URI->getUserInfo();
            $parsed = explode(':', $userInfos);
            
            return $parsed[0];
        }
        else
        {
            return $this->getServerParam('PHP_AUTH_USER');
        }
    }
    
    /**
     * @return string|null 
     */
    public function getPassword()
    {
        if ($this->fromURI)
        {
            $this->fromURI = false;
            
            $userInfos = $this->URI->getUserInfo();
            $parsed = explode(':', $userInfos);
            
            return isset($parsed[1]) ? $parsed[1] : null;
        }
        else
        {
            return $this->getServerParam('PHP_AUTH_PW');
        }
    }
    
    /**
     * @param array $uploadedFiles
     * @return boolean
     */
    public function isValidUploadedFiles(array $uploadedFiles)
    {
        foreach ($uploadedFiles as $file) 
        {
            if (is_array($file)) 
            {
                $this->isValidUploadedFiles($file);
                continue;
            }
            
            if (!$file instanceof UploadedFileInterface)
            {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * @param null|array|object $data
     * @return boolean
     */
    public function isValidParsedBody($data)
    {
        return true;
    }
}
