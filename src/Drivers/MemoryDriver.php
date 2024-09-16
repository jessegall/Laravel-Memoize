<?php

namespace JesseGall\LaravelMemoize\Drivers;

class MemoryDriver implements DriverInterface
{

    private static array $cache = [];

    public function get(string $target = null, string $method = null): mixed
    {
        if ($target === null) {
            return self::$cache;
        }

        if ($method === null) {
            return self::$cache[$target] ?? [];
        }

        return self::$cache[$target][$method] ?? null;
    }

    public function set(string $target, string $method, mixed $value): void
    {
        self::$cache[$target][$method] = $value;
    }

    public function has(string $target, string $method): bool
    {
        return isset(self::$cache[$target][$method]);
    }

    public function forget(string $target = null): void
    {
        if ($target === null) {
            self::$cache = [];
        } else {
            unset(self::$cache[$target]);
        }
    }

}