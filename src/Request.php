<?php
namespace Clicalmani\Psr7;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements ServerRequestInterface
{
    protected $requestTarget;

    protected $queryParams;

    protected array $attributes;

    protected $parsedBody;

    public function __construct(
        protected string $method,
        protected UriInterface $uri,
        HeadersInterface $headers,
        protected array $cookies,
        protected array $serverParams,
        StreamInterface $body,
        protected ?array $uploadedFiles = []
    )
    {
        $this->headers = $headers;
        $this->body = $body;

        if (isset($this->serverParams['SERVER_PROTOCOL'])) {
            $this->protocolVersion = str_replace('HTTP/', '', $this->serverParams['SERVER_PROTOCOL']);
        }

        if (!isset($this->headers['host']) && $this->uri->getHost() !== '') {
            $this->headers['host'] = $this->uri->getHost();
        }
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): RequestInterface
    {
        $clone = clone $this;
        $clone->method = $method;
        return $clone;
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget) return $this->requestTarget;

        if (NULL === $this->uri) return '/';

        $uri = '/' . ltrim($this->uri, '/');
        $query = $this->uri->getQuery();

        return $query ? $uri . '?' . $query: $uri;
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;
        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if ('' !== $uri->getHost()) {
            if (!$preserveHost || !isset($this->headers['Host'])) {
                $this->headers['Host'] = $uri->getHost();
                return $clone;
            }
        }

        return $clone;
    }

    public function getCookieParams(): array
    {
        return $this->cookies;
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->cookies = $cookies;

        return $clone;
    }

    public function getQueryParams(): array
    {
        if ( is_array($this->queryParams) ) return $this->queryParams;
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->queryParams = $query;

        return $clone;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;

        return $clone;
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    public function withoutAttribute(string $name): ServerRequestInterface
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        if (!is_null($data) && !is_object($data) && !is_array($data)) {
            throw new \InvalidArgumentException("Parsed body must be null, an object, or an array");
        }

        $clone = clone $this;
        $clone->parsedBody = $data;

        return $clone;
    }
}