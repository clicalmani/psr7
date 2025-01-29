<?php
namespace Clicalmani\Psr7;

class Header implements HeaderInterface
{
    /**
     * @var string $name
     */
    public string $name = '';

    /**
     * @var array $value
     */
    public array $value = [];

    public function __construct(string $name, ?array $value = [])
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function line(): string
    {
        return implode(',', $this->value);
    }
}