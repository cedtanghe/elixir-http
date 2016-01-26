<?php

namespace Elixir\HTTP;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class Cookie 
{
    /**
     * @param string $value
     * @return Cookie
     */
    public static function fromString($value)
    {
        $segments = explode(';', $value);
        $part = explode('=', array_shift($segments));
        
        $data = [
            'name' => trim($part[0]), 
            'value' => rawurldecode(trim($part[1])), 
            'expires' => 0, 
            'path' => '', 
            'domain' => '', 
            'secure' => false, 
            'httponly' => false
        ];
        
        foreach ($segments as $segment)
        {
            $part = explode('=', $segment);
            $key = trim(strtolower($part[0]));
            
            switch ($key)
            {
                case 'expires':
                    $date = \DateTime::createFromFormat('D, d-M-Y H:i:s \G\M\T', trim($part[1]), new \DateTimeZone('GMT'));
                    $data[$key] = false !== $date ? $date->getTimestamp() : 0;
                break;
                case 'path':
                case 'domain':
                    $data[$key] = trim($part[1]);
                break;
                case 'secure':
                case 'httponly':
                    $data[$key] = true;
                break;
            }
        }
        
        return new static(
            $data['name'],
            $data['value'],
            $data['expires'],
            $data['path'],
            $data['domain'],
            $data['secure'],
            $data['httponly']
        );
    }
    
    /**
     * @var string 
     */
    protected $name;
    
    /**
     * @var string|array 
     */
    protected $value;
    
    /**
     * @var int 
     */
    protected $expires;
    
    /**
     * @var string 
     */
    protected $path;
    
    /**
     * @var string 
     */
    protected $domain;
    
    /**
     * @var boolean 
     */
    protected $secure;
    
    /**
     * @var boolean 
     */
    protected $HTTPOnly;
    
    /**
     * @param string $name
     * @param string|array $value
     * @param integer|string|\DateTime $expires
     * @param string $path
     * @param string $domain
     * @param boolean $secure
     * @param boolean $HTTPOnly
     */
    public function __construct($name, $value = '', $expires = 0, $path = '/', $domain = '', $secure = false, $HTTPOnly = false) 
    {
        $this->name = $name;
        $this->value = $value;
        $this->setExpires($expires);
        $this->setPath($path);
        $this->setDomain($domain);
        $this->secure = $secure;
        $this->HTTPOnly = $HTTPOnly;
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|array
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string|array $value
     */
    public function setValue($value)
    {
        $this->value = $pValue;
    }

    /**
     * @return int
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * @param mixed $value
     */
    public function setExpires($value) 
    {
        if ($value instanceof \DateTime) 
        {
            $value = $value->format('U');
        } 
        else if (version_compare(phpversion(), '5.5', '>=') && $value instanceof \DateInterval)
        {
            $value = time() + $value->format('U');
        }
        else if (!is_numeric($value))
        {
            $value = strtotime($value);
        }

        if (empty($value)) 
        {
            $value = 0;
        }

        $this->expires = $value;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $value
     */
    public function setPath($value) 
    {
        if (empty($value))
        {
            $value = '/';
        }

        $this->path = $value;
    }

    /**
     * @return string
     */
    public function getDomain() 
    {
        return $this->domain;
    }

    /**
     * @param string $value
     */
    public function setDomain($value)
    {
        if (!empty($value))
        {
            if (0 === strpos(strtolower($value), 'www.')) 
            {
                $value = substr($value, 4);
            }

            $value = '.' . $value;

            if (false !== ($port = strpos($value, ':')))
            {
                $value = substr($value, 0, $port);
            }
        }

        $this->domain = $value;
    }

    /**
     * @return boolean
     */
    public function isSecure() 
    {
        return $this->secure;
    }

    /**
     * @param boolean $value
     */
    public function setSecure($value)
    {
        $this->secure = $value;
    }

    /**
     * @return boolean
     */
    public function isHTTPOnly() 
    {
        return $this->HTTPOnly;
    }

    /**
     * @param boolean $value
     */
    public function setHTTPOnly($value) 
    {
        $this->HTTPOnly = $value;
    }
    
    /**
     * @return string|array
     */
    public function formatToString()
    {
        $name = rawurlencode($this->name);
        $cookies = [];

        if (is_array($this->value))
        {
            foreach ($this->value as $key => $value)
            {
                $cookies[$name . '[' . $key . ']'] = rawurlencode($value);
            }
        } 
        else
        {
            $cookies[$name] = rawurlencode($this->value);
        }

        $lines = [];

        foreach ($cookies as $key => $value) 
        {
            $cookie = $key . '=';

            if ('' !== (string)$value) 
            {
                $cookie .= $value;

                if ($this->expires != 0)
                {
                    $date = new \DateTime('@' . $this->expires, new \DateTimeZone('GMT'));
                    $cookie .= '; expires=' . $date->format('D, d-M-Y H:i:s \G\M\T');
                }
            } 
            else 
            {
                $date = new \DateTime('@' . (time() - 3600), new \DateTimeZone('GMT'));
                $cookie .= 'null; expires=' . $date->format('D, d-M-Y H:i:s \G\M\T');
            }

            if (!empty($this->path))
            {
                $cookie .= '; path=' . $this->path;
            }

            if (!empty($this->domain))
            {
                $cookie .= '; domain=' . $this->domain;
            }

            if ($this->secure) 
            {
                $cookie .= '; secure';
            }

            if ($this->HTTPOnly) 
            {
                $cookie .= '; httponly';
            }

            $lines[] = $cookie;
        }

        return count($lines) > 0 ? $lines[0] : $lines;
    }
    
    /**
     * @return boolean
     */
    public function send()
    {
        if (is_array($this->value))
        {
            foreach ($this->value as $key => $value) 
            {
                if (true !== setcookie($this->name . '[' . $key . ']', (string)$value, $this->expires, $this->path, $this->domain, $this->secure, $this->HTTPOnly))
                {
                    return false;
                }
            }
        } 
        else
        {
            return setcookie($this->name, $this->value, $this->expires, $this->path, $this->domain, $this->secure, $this->HTTPOnly);
        }
    }

    /**
     * @ignore
     * @throws \RuntimeException
     */
    public function __toString()
    {
        if (is_array($this->value)) 
        {
            throw new \RuntimeException('The cookie contains multiple values and therefore can not be made as a single string.');
        }

        return $this->formatToString();
    }
}
