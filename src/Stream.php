<?php
namespace Clicalmani\Psr7;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    /**
     * @var ?resource
     */
    protected $stream;

    /**
     * @var ?array
     */
    protected $meta;

    /**
     * @var ?bool
     */
    protected ?bool $readable = null;

    /**
     * @var ?bool
     */
    protected ?bool $writable = null;

    /**
     * @var ?bool
     */
    protected ?bool $seekable = null;

    /**
     * @var ?bool
     */
    protected ?bool $isPipe = null;

    /**
     * @var ?bool
     */
    protected bool $finished = false;

    /**
     * @var ?int
     */
    protected ?int $size = null;

    /**
     * @var ?\Psr\Http\Message\StreamInterface
     */
    protected ?StreamInterface $cache = null;

    public function __construct(
        $stream, 
        bool $isPipe = false,
        ?StreamInterface $cache = null
    )
    {
        $this->stream = $stream;
        $this->cache = $cache;
        $this->isPipe = $isPipe;
    }

    public function getMetadata(?string $key = null)
    {
        if (!$this->stream) return null;

        $this->meta = stream_get_meta_data($this->stream);

        if (!$key) return $this->meta;

        return $this->meta[$key] ?? null;
    }

    public function detach()
    {
        $current_resource = $this->stream;
        $this->stream = null;
        $this->meta = null;
        $this->readable = null;
        $this->writable = null;
        $this->seekable = null;
        $this->size = null;
        $this->isPipe = null;
        $this->cache = null;
        $this->finished = false;

        return $current_resource;
    }

    public function __toString(): string
    {
        if ($this->stream) return '';

        if ($this->cache && $this->finished) {
            $this->cache->rewind();
            return $this->cache->getContents();
        }

        if ($this->isSeekable()) {
            $this->rewind();
        }

        return $this->getContents();
    }

    public function close(): void
    {
        if ($this->stream) {
            if ($this->isPipe()) {
                pclose($this->stream);
            } else {
                fclose($this->stream);
            }
        }

        $this->detach();
    }

    public function getSize(): ?int
    {
        if ($this->stream && !$this->size) {
            $stats = fstat($this->stream);

            if ($stats) {
                $this->size = !$this->isPipe() ? $stats['size'] : null;
            }
        }

        return $this->size;
    }

    public function getStream()
    {
        return $this->stream;
    }

    public function tell(): int
    {
        $position = false;

        if ($this->stream) {
            $position = ftell($this->stream);
        }

        if ($position === false || $this->isPipe()) {
            throw new \RuntimeException(
                sprintf("Could not get the position of the pointer in stream.")
            );
        }

        return $position;
    }

    public function eof(): bool
    {
        return !$this->stream || feof($this->stream);
    }

    public function isReadable(): bool
    {
        if ( isset($this->readable) ) return $this->readable;

        $this->readable = false;

        if ($this->stream) {
            $mode = $this->getMetadata('mode');

            if ( is_string($mode) && strstr($mode, 'r') !== FALSE) $this->readable = true;
        }

        return $this->readable;
    }

    public function isWritable(): bool
    {
        if ( ! isset($this->writable) ) {
            $this->writable = false;

            if ($this->stream) {
                $mode = $this->getMetadata('mode');

                if ( FALSE !== is_string($mode) && strstr($mode, 'w') ) $this->writable = true;
            }
        }

        return $this->writable;
    }

    public function isSeekable(): bool
    {
        if ( ! isset($this->seekable) ) {
            $this->seekable = false;

            if ($this->stream) {
                $this->seekable = !$this->isPipe() && $this->getMetadata('seekable');
            }
        }

        return $this->seekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if ( !$this->isSeekable() || ($this->stream && fseek($this->stream, $offset, $whence) === -1)) 
            throw new \RuntimeException("Could not seek in stream");
    }

    public function rewind(): void
    {
        if ( !$this->isSeekable() || ($this->stream && rewind($this->stream) === FALSE))
            throw new \RuntimeException("Could not rewind stream");
    }

    public function read(int $length): string
    {
        $data = false;
        
        if ( $this->isReadable() && $this->stream && $length > 0 ) {
            $data = fread($this->stream, $length);
        }

        if ( is_string($data) ) {
            if ($this->cache) {
                $this->cache->write($data);
            }

            if ($this->eof()) {
                $this->finished = true;
            }

            return $data;
        }

        throw new \RuntimeException("Could not read from stream");
    }

    public function write(string $string): int
    {
        $complete = false;
        
        if ($this->isWritable() && $this->stream) {
            $complete = fwrite($this->stream, $string);
        }

        if (FALSE !== $complete) {
            $this->size = null;
            return $complete;
        }

        throw new \RuntimeException("Could not write to stream");
    }

    public function getContents(): string
    {
        if ($this->cache && $this->finished) {
            $this->cache->rewind();
            return $this->cache->getContents();
        }

        $contents = null;

        if ($this->stream) {
            $contents = stream_get_contents($this->stream);
        }

        if ( isset($contents) ) {
            if ($this->cache) {
                $this->cache->write($contents);
            }

            if ($this->eof()) {
                $this->finished = true;
            }

            return $contents;
        }

        throw new \RuntimeException("Could not get the content of stream");
    }

    public function isPipe() : bool
    {
        if ( ! isset($this->isPipe) ) {
            $this->isPipe = false;

            if ($this->stream) {
                $stats = fstat($this->stream);

                if ( is_array($stats) ) {
                    $this->isPipe = ($stats['mode'] & 0010000) !== 0;
                }
            }
        }

        return $this->isPipe;
    }

    public static function create(string $content = '')
    {
        $resource = fopen('php://temp', 'rw+');

        if (!is_resource($resource)) {
            throw new \RuntimeException('Could not open temporary file stream.');
        }

        fwrite($resource, $content);
        rewind($resource);

        return self::createFromResource($resource);
    }

    public static function createFromResource($resource, ?StreamInterface $cache = null, bool $isPipe = false): StreamInterface
    {
        return new Stream($resource, $isPipe, $cache);
    }

    public static function createFromFile(
        string $filename,
        string $mode = 'r',
        ?StreamInterface $cache = null,
        bool $isPipe = false
    ): StreamInterface {
        set_error_handler(
            static function (int $errno, string $errstr) use ($filename, $mode): void {
                throw new \RuntimeException(
                    "Unable to open $filename using mode $mode: $errstr",
                    $errno
                );
            }
        );

        try {
            if (FALSE === $isPipe) $resource = fopen($filename, $mode);
            else $resource = popen($filename, $mode);
        } catch (\ValueError $exception) {
            throw new \RuntimeException("Unable to open $filename using mode $mode: " . $exception->getMessage());
        } finally {
            restore_error_handler();
        }

        if (!is_resource($resource)) {
            throw new \RuntimeException(
                "StreamFactory::createStreamFromFile() could not create resource from file `$filename`"
            );
        }

        return new Stream($resource, $isPipe, $cache);
    }

    /**
     * @inheritDoc
     */
    public function pipe()
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException("Source stream is not readable");
        }

        ob_start();

        fpassthru($this->stream);

        $buffered = '';
        while (0 < ob_get_level()) {
            $buffered = ob_get_clean() . $buffered;
        }

        echo $buffered;

        $this->close();

        exit;
    }
}