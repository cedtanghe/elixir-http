<?php

namespace Elixir\HTTP;

use Elixir\HTTP\Request;
use Elixir\STDLib\ArrayUtils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

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
     * @param string|UriInterface|null $URI
     * @param array $config
     * @throws \InvalidArgumentException
     */
    public function __construct($URI = null, array $config = [])
    {
        $config += ['body' => 'php://input'];
        
        parent::__construct($URI, $config);
        
        // Server params
        if (!empty($config['server_params']))
        {
            $this->serverParams = (array)$config['server_params'];
        }
        
        // Cookie params
        if (!empty($config['cookie_params']))
        {
            $this->cookieParams = (array)$config['cookie_params'];
        }
        
        // Query params
        if (!empty($config['query_params']))
        {
            $this->queryParams = (array)$config['query_params'];
        }
        
        // Uploaded files
        if (!empty($config['uploaded_files']))
        {
            if (!$this->isValidUploadedFiles($config['uploaded_files']))
            {
                throw new \InvalidArgumentException('Invalid structure is provided in uploaded files.');
            }

            $this->uploadedFiles = $config['uploaded_files'];
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
