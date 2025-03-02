<?php
namespace Clicalmani\Psr7;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

class Message implements MessageInterface
{
    protected string $protocolVersion = '1.1';

    protected static array $validProtocolVersions = [
        '1.0' => true,
        '1.1' => true,
        '2.0' => true,
        '2'   => true
    ];

    /**
     * @var \Clicalmani\Psr7\HeadersInterface
     */
    protected $headers;

    /**
     * @var \Psr\Http\Message\StreamInterface
     */
    protected $body;

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        if ( isset(self::$validProtocolVersions[$version]) && (self::$validProtocolVersions[$version] === true) ) {
            $clone = clone $this;
            $clone->protocolVersion = $version;
            return $clone;
        }

        throw new \InvalidArgumentException(
            sprintf("Invalid HTTP version %s", $version)
        );
    }

    public function getHeaders(): array
    {
        return $this->headers->all();
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function getHeader(string $name): array
    {
        return $this->headers[$name];
    }

    public function getHeaderLine(string $name): string
    {
        return $this->headers[$name]?->line() ?? '';
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $header = new Header($name, (array)$value);
        $clone = clone $this;
        $clone->headers[] = $header;

        return $clone;
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $new_header = new Header($name, (array)$value);
        $clone = clone $this;
        $clone->headers[] = $new_header;
        return $clone;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $clone = clone $this;
        $clone->headers = array_filter($this->headers->all(), function($header) use ($name) {
            return $header->name !== $name;
        });

        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    public function __clone()
    {
        $this->headers = clone $this->headers;
    }
}