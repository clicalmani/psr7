<?php
namespace Clicalmani\Psr7;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    public function __construct(
        protected string $scheme,
        protected string $host,
        protected ?int $port = null,
        protected ?string $user = '',
        protected ?string $password = '',
        protected ?string $path = '/',
        protected ?string $query = '',
        protected ?string $fragment = ''
    )
    {
        
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function withScheme(string $scheme): UriInterface
    {
        $clone = clone $this;
        $clone->scheme = $scheme;
        return $clone;
    }

    public function getHost() : string
    {
        return $this->host;
    }

    public function withHost(string $host): UriInterface
    {
        $clone = clone $this;
        $clone->host = $host;

        return $clone;
    }

    public function getQuery() : string
    {
        return http_build_query($_GET);
    }

    public function withQuery(string $query): UriInterface
    {
        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    public function getPath(): string
    {
        return '/' . ltrim($this->path, '/');
    }

    public function withPath(string $path): UriInterface
    {
        $clone = clone $this;
        $clone->path = $path;

        return $clone;
    }

    public function getUserInfo(): string
    {
        return $this->password ? "{$this->user}:{$this->password}": $this->user;
    }

    public function getAuthority(): string
    {
        $info = $this->getUserInfo();
        return ($info ? "{$info}@": "") . "{$this->getHost()}" . (NULL !== $this->port ? ":{$this->port}": "");
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $clone = clone $this;
        $clone->user = $user;
        $clone->password = '' !== $clone->user ? $password: '';

        return $clone;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function withPort(?int $port): UriInterface
    {
        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withFragment(string $fragment): UriInterface
    {
        $clone = clone $this;
        $clone->fragment = $fragment;

        return $clone;
    }

    public function __toString(): string
    {
        $scheme = $this->scheme;
        $authority = $this->getAuthority();
        $path = $this->getPath();
        $query = $this->getQuery();
        $fragment = $this->getFragment();

        return ('' !== $scheme ? "{$scheme}:": "") . 
               ('' !== $authority ? "//{$authority}": "") . $path . 
               ('' !== $query ? "?{$query}": "") . 
               ('' !== $fragment ? "#{$fragment}": "");
    }
}