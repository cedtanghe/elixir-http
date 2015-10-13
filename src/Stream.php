<?php

namespace Elixir\HTTP;

use Psr\Http\Message\StreamInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class Stream implements StreamInterface
{
    /**
     * @var array
     */
    protected $metadata;

    /**
     * @var resource
     */
    protected $resource;
    
    /**
     * @param string|resource $stream
     * @param array $options
     */
    public function __construct($stream, array $options = [])
    {
        $options += [
            'mode' => 'r',
            'content' => null
        ];
        
        $this->attach($stream, $options['mode']);
        
        if (null !== $options['content'])
        {
            $this->write($options['content']);
        }
    }
    
    /**
     * @ignore
     */
    public function __destruct()
    {
        $this->close();
    }
    
    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if (!$this->resource) 
        {
            return;
        }
        
        $resource = $this->detach();
        fclose($resource);
    }
    
    /**
     * @param string|resource $resource
     * @param string $mode
     * @throws \InvalidArgumentException
     */
    public function attach($resource, $mode = 'r')
    {
        $error = null;
        
        if (is_string($stream)) 
        {
            set_error_handler(function($e) use (&$error) 
            {
                $error = $e;
            }, E_WARNING);
            
            $resource = fopen($stream, $mode);
            restore_error_handler();
        }
        
        if ($error || !is_resource($resource) || 'stream' !== get_resource_type($resource)) 
        {
            throw new \InvalidArgumentException('Invalid stream.');
        }
        
        $this->resource = $resource;
    }
    
    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        
        return $resource;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        if (null === $this->resource)
        {
            return null;
        }
        
        $stats = fstat($this->resource);
        return $stats['size'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        $result = ftell($this->resource);
        
        if (false === $result)
        {
            throw new \RuntimeException('Error occurred during tell operation');
        }
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        return feof($this->resource);
    }
    
    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        return $this->getMetadata('seekable');
    }
    
    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->isSeekable() || 0 !== fseek($this->resource, $offset, $whence))
        {
            throw new \RuntimeException('Stream is not seekable');
        }
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        return $this->seek(0);
    }
    
    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        $mode = $this->getMetadata('mode');
        
        foreach (['x', 'w', 'c', 'a', '+'] as $m)
        {
            if (strstr($mode, $m))
            {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        if (!$this->isWritable() || false === ($result = fwrite($this->resource, $string))) 
        {
            throw new \RuntimeException('Stream is not writable');
        }
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        $mode = $this->getMetadata('mode');
        return strstr($mode, 'r') || strstr($mode, '+');
    }
    
    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        if (!$this->isReadable() || false === ($result = fread($this->resource, $length))) 
        {
            throw new \RuntimeException('Stream is not readable');
        }
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        if (!$this->isReadable() || false === ($result = stream_get_contents($this->resource))) 
        {
            throw new \RuntimeException('Stream is not readable');
        }
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        if (null === $this->metadata)
        {
            $this->metadata = stream_get_meta_data($this->resource);
        }
        
        if (null === $key)
        {
            return $this->metadata;
        }
        
        if (!array_key_exists($key, $this->metadata))
        {
            return null;
        }
        
        return $this->metadata[$key];
    }
    
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (!$this->isReadable()) 
        {
            return '';
        }
        
        try 
        {
            $this->rewind();
            return $this->getContents();
        } 
        catch (\RuntimeException $e)
        {
            return '';
        }
    }
}
