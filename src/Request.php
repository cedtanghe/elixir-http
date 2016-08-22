<?php

namespace Elixir\HTTP;

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
    protected $method = 'GET';

    /**
     * @var string
     */
    protected $requestTarget;

    /**
     * @param string|UriInterface|null $URI
     * @param array                    $config
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($URI = null, array $config = [])
    {
        // Method
        if (!empty($config['method'])) {
            if (!$this->isValidMethod($config['method'])) {
                throw new \InvalidArgumentException('Unsupported HTTP method.');
            }

            $this->method = $config['method'];
        }

        // Body
        if (isset($config['body']) && ($config['body'] instanceof StreamInterface)) {
            $this->body = $config['body'];
        } else {
            $this->body = StreamFactory::create('php://temp', ['mode' => 'wb+']);
        }

        // Headers
        if (!empty($config['headers'])) {
            $headers = [];

            foreach ($config['headers'] as $header => &$values) {
                $name = $this->filterHeader($header);

                foreach ((array) $values as $value) {
                    if (!$this->isValidHeaderValue($value)) {
                        throw new \InvalidArgumentException(sprintf('Invalid header value for "%s".', $header));
                    }

                    $headers[$name][] = $value;
                }
            }

            $this->headers = $headers;
        }

        // Protocol
        if (!empty($config['protocol'])) {
            $this->protocol = $config['protocol'];
        }

        // Request target
        if (isset($config['request_target'])) {
            $this->requestTarget = $config['request_target'];
        }

        // URI
        $this->URI = $this->prepareURI();
    }

    /**
     * @param string|UriInterface|null $URI
     *
     * @return UriInterface
     */
    protected function prepareURI($URI = null)
    {
        if (is_string($URI)) {
            $URI = new URI($URI);
        }

        return $URI ?: new URI();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTarget()
    {
        if (null !== $this->requestTarget) {
            return $this->requestTarget;
        }

        $target = $this->URI->getPath();
        $query = $this->URI->getQuery();

        if ($query) {
            $target .= '?'.$query;
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
        if (!$this->isValidMethod($method)) {
            throw new \InvalidArgumentException('Unsupported HTTP method.');
        }

        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    /**
     * @return bool
     */
    public function isHead()
    {
        return $this->getMethod() === 'HEAD';
    }

    /**
     * @return bool
     */
    public function isGet()
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * @return bool
     */
    public function isQuery()
    {
        $method = $this->getMethod();

        return $method === 'GET' || $method === 'HEAD';
    }

    /**
     * @return bool
     */
    public function isPost()
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * @return bool
     */
    public function isPut()
    {
        return $this->getMethod() === 'PUT';
    }

    /**
     * @return bool
     */
    public function isDelete()
    {
        return $this->getMethod() === 'DELETE';
    }

    /**
     * @return bool
     */
    public function isConnect()
    {
        return $this->getMethod() === 'CONNECT';
    }

    /**
     * @return bool
     */
    public function isOptions()
    {
        return $this->getMethod() === 'OPTIONS';
    }

    /**
     * @return bool
     */
    public function isTrace()
    {
        return $this->getMethod() === 'TRACE';
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

        if (($preserveHost && $this->hasHeader('Host')) || !$URI->getHost()) {
            return $new;
        }

        $host = $URI->getHost();
        $port = $URI->getPort();

        if ($port) {
            $host .= ':'.$port;
        }

        $new->headers['Host'] = [$host];

        return $new;
    }

    /**
     * @return string
     */
    public function getURL()
    {
        return (string) $this->URI;
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public function isValidMethod($method)
    {
        return in_array($method, ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE']);
    }
}
