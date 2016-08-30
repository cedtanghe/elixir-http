<?php

namespace Elixir\HTTP;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class UploadedFile implements UploadedFileInterface
{
    /**
     * @param array $file
     *
     * @return array|UploadedFileInterface|null
     */
    public static function create(array $file)
    {
        if (empty($file)) {
            return null;
        }
        
        if (is_array($file['tmp_name'])) {
            $uploadedFiles = [];

            foreach (array_keys($file['tmp_name']) as $key) {
                $uploadedFiles[$key] = new static(
                    $file['tmp_name'][$key],
                    $file['size'][$key],
                    $file['error'][$key],
                    $file['name'][$key],
                    $file['type'][$key]
                );
            }

            return $uploadedFiles;
        }
        
        return new static(
            $file['tmp_name'],
            $file['size'],
            $file['error'],
            $file['name'],
            $file['type']
        );
    }

    /**
     * @var null|StreamInterface;
     */
    protected $stream;

    /**
     * @var null|string;
     */
    protected $file;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var int
     */
    protected $error;

    /**
     * @var string|null
     */
    protected $clientFilename;

    /**
     * @var string|null
     */
    protected $clientMediaType;

    /**
     * @var bool
     */
    protected $moved = false;

    /**
     * @var string
     */
    protected $targetPath = null;

    /**
     * @param string|resource|StreamInterface $streamOrFile
     * @param int                             $size
     * @param int                             $error
     * @param string                          $clientFilename
     * @param string                          $clientMediaType
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($streamOrFile, $size, $error, $clientFilename = null, $clientMediaType = null)
    {
        if ($error === UPLOAD_ERR_OK) {
            if (is_string($streamOrFile)) {
                $this->file = $streamOrFile;
            } elseif (is_resource($streamOrFile)) {
                $this->stream = StreamFactory::create($streamOrFile);
            } else {
                if (!$streamOrFile instanceof StreamInterface) {
                    throw new \InvalidArgumentException('Invalid stream or file.');
                }

                $this->stream = $streamOrFile;
            }
        }

        $this->size = $size;
        $this->error = $error;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }

    /**
     * {@inheritdoc}
     */
    public function getStream()
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Cannot retrieve stream due to upload error.');
        }

        if ($this->moved) {
            throw new \RuntimeException('Cannot retrieve stream after it has already been moved.');
        }

        $this->stream = ($this->stream instanceof StreamInterface) ? $this->stream : StreamFactory::create($this->file);

        return $this->stream;
    }

    /**
     * @return bool
     */
    public function isUploaded()
    {
        return is_uploaded_file($this->file);
    }

    /**
     * @return bool
     */
    public function isMoved()
    {
        return $this->moved;
    }

    /**
     * @return string
     */
    public function getTargetPath()
    {
        return $this->targetPath;
    }

    /**
     * @param string $value
     */
    public function setTargetPath($value)
    {
        $this->targetPath = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function moveTo($targetPath)
    {
        $targetPath = $targetPath ?: $this->targetPath;

        if (!$targetPath) {
            throw new \InvalidArgumentException('Path file is invalid.');
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Cannot move file due to upload error.');
        }

        if ($this->moved) {
            throw new \RuntimeException('Cannot move file because the file is already moved.');
        }

        if (empty(PHP_SAPI) || 0 === strpos(PHP_SAPI, 'cli') || !$this->file) {
            $handle = fopen($targetPath, 'wb+');

            if (false === $handle) {
                throw new \RuntimeException('Error occurred while moving uploaded file.');
            }

            $stream = $this->getStream();
            $stream->rewind();

            while (!$stream->eof()) {
                fwrite($handle, $stream->read(4096));
            }

            fclose($handle);
        } elseif (false === move_uploaded_file($this->file, $targetPath)) {
            throw new \RuntimeException('Error occurred while moving uploaded file.');
        }

        $this->moved = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }
}
