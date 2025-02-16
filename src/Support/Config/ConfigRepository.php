<?php

namespace PHPacker\PHPacker\Support\Config;

class ConfigRepository
{
    public function __construct(
        protected array $data
    ) {}

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function all(): object
    {
        return (object) $this->data;
    }

    public function merge(array $data): object
    {
        $this->data = array_merge($this->data, $data);

        return $this->all();
    }
}
