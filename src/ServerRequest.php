<?php

namespace Elixir\HTTP;

use Elixir\HTTP\Request;
use Elixir\STDLib\ArrayUtils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class Request extends Request implements ServerRequestInterface
{
    /**
     * @var string
     */
    const SERVER = 'server';
    
    /**
     * @var string
     */
    const COOKIE = 'cookie';
    
    /**
     * @var string
     */
    const QUERY = 'query';
    
    /**
     * @var string
     */
    const UPLOADED_FILES = 'uploaded_files';
    
    /**
     * @var string
     */
    const PARSED_BODY = 'parsed_body';
    
    /**
     * @var string
     */
    const ATTRIBUTES = 'attributes';
    
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
     * @param mixed $key
     * @param mixed $default
     * @param array $providers
     * @return mixed
     */
    public function get($key, $default = null, array $providers = [self::ATTRIBUTES, self::QUERY, self::PARSED_BODY])
    {
        $noResult = '__no_result__';
        $method = [
            self::ATTRIBUTES => 'getAttribute',
            self::COOKIE => 'getCookieParam',
            self::PARSED_BODY => 'getPostParam',
            self::QUERY => 'getQueryParam',
            self::SERVER => 'getServerParam',
            self::UPLOADED_FILES => 'getUploadedFile'
        ];
        
        foreach ($providers as $provider)
        {
            $m = isset($method[$provider]) ? $method[$provider] : null;
            
            if ($m)
            {
                $result = $this->$m($key, $noResult);

                if ($result !== $noResult)
                {
                    return $result;
                }
            }
        }
        
        return is_callable($default) ? call_user_func($default) : $default;
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
}
