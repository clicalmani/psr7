<?php
namespace Clicalmani\Psr7;

class Headers extends \ArrayObject implements HeadersInterface
{
    public function all() : array
    {
        $result = [];

        /** @var \Clicalmani\Psr7\Header */
        foreach ($this as $header) {
            $result[$header->name] = $header->line();
        }

        return $result;
    }

    public function get(string $name) : ?HeaderInterface
    {
        /** @var \Clicalmani\Psr7\Header */
        foreach ($this as $header) {
            if ($header->name === $name) return $header->value;
        }

        return null;
    }

    public function set(string $name, array $value) : void
    {
        $this[$name] = $value;
    }

    public function offsetSet(mixed $key, mixed $value): void
    {
        parent::offsetSet($key, $value);
    }

    public static function fromArray(array $array) : HeadersInterface
    {
        $headers = new self;

        foreach ($array as $key => $value) {
            $headers[] = new Header($key, (array)$value);
        }

        return $headers;
    }
}