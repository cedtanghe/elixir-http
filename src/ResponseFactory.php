<?php

namespace Elixir\HTTP;

use Psr\Http\Message\ResponseInterface as PSRResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PSRServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class ResponseFactory
{
    /**
     * @param PSRServerRequestInterface $request
     *
     * @return bool
     */
    public static function isNotModified(PSRServerRequestInterface $request)
    {
        $lastModified = $this->getHeaderLine('Last-Modified');
        $ifModifiedSince = $request->getHeaderLine('If-Modified-Since');

        if (null !== $lastModified && null !== $ifModifiedSince) {
            if ($lastModified === strtotime($ifModifiedSince)) {
                return true;
            }
        }

        $etag = $this->getHeaderLine('Etag');
        $ifNoneMatch = $request->getHeaderLine('If-None-Match');

        if (null !== $etag && null !== $ifNoneMatch) {
            if ($etag === $ifNoneMatch) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param PSRResponseInterface|null $response
     *
     * @return PSRResponseInterface
     */
    public static function setNotModified(PSRResponseInterface $response = null)
    {
        if (null === $response) {
            $response = static::create(['status_code' => 304]);
        } else {
            $response = $response->withStatus(304);
            $response = $response->withBody(StreamFactory::create('php://temp', ['mode' => 'r']));

            foreach (array('Allow', 'Content-Encoding', 'Content-Language', 'Content-Length', 'Content-MD5', 'Content-Type', 'Last-Modified') as $header) {
                $response = $response->withoutHeader($header);
            }
        }

        return $response;
    }

    /**
     * @param string $string
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     */
    public static function fromString($string)
    {
        $lines = explode("\r\n", $string);
        $line = array_shift($lines);

        if (!$line || !preg_match('/^(?P<protocol>HTTP\/1\.(0|1)) (?P<status>\d{3}).*$/', $line, $matches)) {
            throw new \InvalidArgumentException('Response text is not valid.');
        }

        $protocol = $matches['protocol'];
        $status = $matches['status'];
        $headers = [];
        $content = [];
        $type = 'header';

        while (count($lines) > 0) {
            $line = array_shift($lines);

            if ($type === 'header' && empty($line)) {
                $type = 'content';
                continue;
            }

            if ($type === 'header') {
                list($name, $value) = array_map('trim', explode(':', $line));
                $headers[$name][] = $value;
            } else {
                $content[] = $line;
            }
        }

        return new Response([
            'content' => implode("\r\n", $content),
            'status_code' => $status,
            'protocol' => $protocol,
            'headers' => $headers,
        ]);
    }

    /**
     * @param PSRResponseInterface $response
     *
     * @return ResponseInterface
     */
    public static function convert(PSRResponseInterface $response)
    {
        return static::create([
            'status_code' => $response->getStatusCode(),
            'reason_phrase' => $response->getReasonPhrase(),
            'protocol' => $response->getProtocolVersion(),
            'headers' => $response->getHeaders(),
            'body' => $response->getBody(),
        ]);
    }

    /**
     * @param array $config
     *
     * @return Response
     */
    public static function create(array $config = [])
    {
        return new Response($config);
    }

    /**
     * @return ResponseInterface
     */
    public static function createEmpty()
    {
        return static::create(['status_code' => 204]);
    }

    /**
     * @param string $HTML
     * @param int    $status
     * @param array  $config
     *
     * @return ResponseInterface
     */
    public static function createHTML($HTML, $status = 200, array $config = [])
    {
        $config['content'] = $HTML;
        $config['status_code'] = $status;
        $config['headers']['content-type'] = ['text/html; charset=UTF-8'];

        return static::create($config);
    }

    /**
     * @param mixed $data
     * @param int   $status
     * @param int   $encodingOptions
     * @param array $config
     *
     * @return ResponseInterface
     */
    public static function createJSON($data, $status = 200, $encodingOptions = 0, array $config = [])
    {
        $JSON = json_encode($data, $encodingOptions);

        $config['content'] = $JSON;
        $config['status_code'] = $status;
        $config['headers']['content-type'] = ['application/json'];

        return static::create($config);
    }
    
    /**
     * @param string $JSON
     * @param int   $status
     * @param array $config
     *
     * @return ResponseInterface
     */
    public static function createJSONFromString($JSON, $status = 200, array $config = [])
    {
        $config['content'] = $JSON;
        $config['status_code'] = $status;
        $config['headers']['content-type'] = ['application/json'];

        return static::create($config);
    }
    
    /**
     * @param string|UriInterface $URI
     * @param int                 $status
     * @param array               $config
     * @param bool                $send
     *
     * @return ResponseInterface|void
     */
    public static function createRedirect($URI, $status = 302, array $config = [], $send = true)
    {
        $config['status_code'] = $status;
        $config['headers']['location'] = [(string) $URI];

        $response = static::create($config);

        if ($send) {
            $response->send();
            exit();
        }

        return $response;
    }
}
