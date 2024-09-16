<?php

namespace JesseGall\LaravelMemoize\Drivers;

class MemoryDriver implements DriverInterface
{

    private static array $cache = [];

    public function getAll(): array
    {
        return self::$cache;
    }

    public function getCacheForTarget(string $targetKey): array
    {
        return self::$cache[$targetKey] ?? [];
    }

    public function getCachedMethodValue(string $targetKey, string $methodKey): mixed
    {
        return self::$cache[$targetKey][$methodKey] ?? null;
    }

    public function setCachedMethodValue(string $targetKey, string $methodKey, mixed $value): void
    {
        self::$cache[$targetKey][$methodKey] = $value;
    }

    public function hasCachedMethod(string $targetKey, string $methodKey): bool
    {
        return isset(self::$cache[$targetKey][$methodKey]);
    }

    public function clearAll(): void
    {
        self::$cache = [];
    }

    public function clearTarget(string $targetKey): void
    {
        unset(self::$cache[$targetKey]);
    }
}