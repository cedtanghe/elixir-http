<?php

namespace Elixir\HTTP;

use Psr\Http\Message\ServerRequestInterface as PSRServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class Response implements ResponseInterface
{
    use MessageTrait;

    /**
     * @var array
     */
    public static $statusCodesAndReasonPhrases = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Statust',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (Experimental)',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * @var string
     */
    protected $reasonPhrase = '';

    /**
     * @var int
     */
    protected $statusCode = 200;

    /**
     * @var string
     */
    protected $charset = 'UTF-8';

    /**
     * @param array $config
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $config = [])
    {
        $config += [
            'status_code' => 200,
            'content' => null,
        ];

        // Body
        if (isset($config['body']) && ($config['body'] instanceof StreamInterface)) {
            $this->body = $config['body'];
        } else {
            $this->body = StreamFactory::create('php://memory', ['mode' => 'wb+']);
        }

        if (null !== $config['content']) {
            $this->body->write($config['content']);
        }

        // Status
        if (!$this->isValidStatus($config['status_code'])) {
            throw new \InvalidArgumentException(sprintf('Invalid status code "%s".', $code));
        }

        // Reason phrase
        if (isset($config['reason_phrase'])) {
            $this->reasonPhrase = $config['reason_phrase'];
        }

        // Protocol
        if (!empty($config['protocol'])) {
            $this->protocol = $config['protocol'];
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
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase()
    {
        if (!$this->reasonPhrase && isset(static::$statusCodesAndReasonPhrases[$this->statusCode])) {
            $this->reasonPhrase = $this->phrases[$this->statusCode];
        }

        return $this->reasonPhrase;
    }

    /**
     * {@inheritdoc}
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        if (!$this->isValidStatus($code)) {
            throw new \InvalidArgumentException(sprintf('Invalid status code "%s".', $code));
        }

        $new = clone $this;
        $new->statusCode = $code;
        $new->reasonPhrase = $reasonPhrase;

        return $new;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        if (null === $this->charset) {
            if ($this->hasHeader('Content-Type')) {
                if (preg_match('/charset=([\w\d\-]+)/i', $this->getHeaderLine('Content-Type'), $matches)) {
                    $this->charset = $matches[1];
                }
            }
        }

        return $this->charset;
    }

    /**
     * @param string $charset
     *
     * @return self
     */
    public function withCharset($charset)
    {
        $new = clone $this;
        $new->charset = $charset;

        return $new;
    }

    /**
     * @return bool
     */
    public function isOk()
    {
        return 200 == $this->statusCode;
    }

    /**
     * @return bool
     */
    public function isNotFound()
    {
        return 404 == $this->statusCode;
    }

    /**
     * @return bool
     */
    public function isForbidden()
    {
        return 403 == $this->statusCode;
    }

    /**
     * @return bool
     */
    public function isInformational()
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * @return bool
     */
    public function isRedirection()
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * @return bool
     */
    public function isClientError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * @return bool
     */
    public function isServerError()
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * @param int $code
     *
     * @return bool
     */
    public function isValidStatus($code)
    {
        if (!is_numeric($code) || is_float($code) || $code < 100 || $code >= 600) {
            return false;
        }

        return true;
    }

    /**
     * @param PSRServerRequestInterface $request
     *
     * @return self
     */
    public function optimize(PSRServerRequestInterface $request = null)
    {
        $new = clone $this;

        // Utility function for set header
        $setHeader = function ($name, $value) use ($new) {
            foreach ($new->headers as $key => $header) {
                if (strtolower($key) == strtolower($name)) {
                    unset($new->headers[$key]);
                }
            }

            $new->headers[$name] = (array) $value;
        };

        // Utility function for remove header
        $removeHeader = function ($name) use ($new) {
            foreach ($new->headers as $key => $header) {
                if (strtolower($key) == strtolower($name)) {
                    unset($new->headers[$key]);
                }
            }
        };

        // Check by status code
        if ($new->hasHeader('Location') && $new->isOk()) {
            $new->statusCode = 302;
        }

        if ($new->statusCode != 304 && $new->statusCode != 204) {
            if (!$new->hasHeader('Content-Type')) {
                $setHeader('Content-Type', 'text/html; charset=UTF-8');
            } else {
                $contentType = $new->getHeaderLine('Content-Type');

                if (0 === strpos($contentType, 'text/') && false === strpos($contentType, 'charset') && $new->getCharset()) {
                    $setHeader('Content-Type', $contentType.'; charset='.$new->getCharset());
                }
            }
        } else {
            $new->body = clone $new->body;
            $new->body->detach();
        }

        // Remove status line
        foreach ($new->getHeaders() as $key => $headers) {
            if (preg_match('/^HTTP\/1\.(0|1) \d{3}.*$/i', $key)) {
                unset($new->getHeaders()[$key]);
            }
        }

        if ('1.1' === $new->getProtocolVersion() && false !== strpos('no-cache', $new->getHeaderLine('Cache-Control'))) {
            $setHeader('Expires', -1);
            $setHeader('Pragma', 'no-cache');
        }

        // Check Cache-control
        // Cache-Control is removed for SSL encrypted downloads when using IE < 9 (http://support.microsoft.com/kb/323308)
        $serverParams = $request ? $request->getServerParams : $_SERVER;
        $HTTPS = isset($serverParams['HTTPS']) ? $serverParams['HTTPS'] : '';

        $userAgent = isset($serverParams['HTTP_USER_AGENT']) ? $serverParams['HTTP_USER_AGENT'] : '';
        $xForwardedProto = isset($serverParams['HTTP_X_FORWARDED_PROTO']) ? $serverParams['HTTP_X_FORWARDED_PROTO'] : '';
        $secure = false;

        if (($HTTPS && $HTTPS !== 'off') || $xForwardedProto === 'https') {
            $secure = true;
        }

        if (false !== stripos($new->getHeaderLine('Content-Disposition'), 'attachment') && preg_match('/MSIE (.*?);/i', $userAgent, $match) == 1 && $secure) {
            if (intval(preg_replace('/(MSIE )(.*?);/', '$2', $match[0])) < 9) {
                $removeHeader('Cache-Control');
            } else {
                $setHeader('Cache-Control', $this->getCacheControl());
            }
        } else {
            $setHeader('Cache-Control', $this->getCacheControl());
        }

        // Sort headers
        ksort($new->headers);

        return $new;
    }

    /**
     * @return string
     */
    protected function getStatusLine()
    {
        return trim(sprintf(
            'HTTP/%s %d %s',
            $this->protocol,
            $this->statusCode,
            $this->getReasonPhrase()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function send($maxBufferLevel = null)
    {
        $new = $this->optimize();

        if (headers_sent()) {
            return false;
        }

        // Emit status line
        header($new->getStatusLine());

        // Emit headers
        foreach ($new->cookies as $cookie) {
            if (!$cookie->send()) {
                return false;
            }
        }

        foreach ($new->headers as $header => $values) {
            $name = $this->filterHeader($header);

            if (strtolower($name) === 'set-cookie') {
                continue;
            }

            $first = true;

            foreach ($values as $value) {
                header(
                    sprintf(
                        '%s: %s',
                        $name,
                        $value
                    ),
                    $first
                );

                $first = false;
            }
        }

        // Emit body
        if (null === $maxBufferLevel) {
            $maxBufferLevel = ob_get_level();
        }

        while (ob_get_level() > $maxBufferLevel) {
            ob_end_flush();
        }

        echo $new->body;

        return true;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $new = $this->optimize();
        $headers = [$new->getStatusLine()];

        foreach ($new->headers as $header => $values) {
            $name = $this->filterHeader($header);

            foreach ($values as $value) {
                $headers[] = sprintf('%s: %s', $name, $value);
            }
        }

        return implode($headers, "\r\n")."\r\n\r\n".$new->body;
    }
}
