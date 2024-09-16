<?php

namespace JesseGall\LaravelMemoize\Drivers;

interface DriverInterface
{

    public function get(string $target = null, string $method = null): mixed;

    public function set(string $target, string $method, mixed $value): void;

    public function has(string $target, string $method): bool;

    public function forget(string $target = null): void;

}