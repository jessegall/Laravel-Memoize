<?php

namespace JesseGall\LaravelMemoize\Drivers;

class CacheDriver implements DriverInterface
{

    const CACHE_KEY = 'memoize';

    public function __construct(
        private readonly int $ttl = 60,
    ) {}

    public function getAll(): array
    {
        return cache()->get(self::CACHE_KEY, []);
    }

    public function getCacheForTarget(string $targetKey): array
    {
        return $this->getAll()[$targetKey] ?? [];
    }

    public function getCachedMethodValue(string $targetKey, string $methodKey): mixed
    {
        return $this->getCacheForTarget($targetKey)[$methodKey] ?? null;
    }

    public function setCachedMethodValue(string $targetKey, string $methodKey, mixed $value): void
    {
        $cache = $this->getAll();
        $cache[$targetKey][$methodKey] = $value;
        $this->put($cache);
    }

    public function hasCachedMethod(string $targetKey, string $methodKey): bool
    {
        return isset($this->getCacheForTarget($targetKey)[$methodKey]);
    }

    public function clearAll(): void
    {
        cache()->forget(self::CACHE_KEY);
    }

    public function clearTarget(string $targetKey): void
    {
        $cache = $this->getAll();
        unset($cache[$targetKey]);
        $this->put($cache);
    }

    private function put(array $cache): void
    {
        cache()->put(self::CACHE_KEY, $cache, $this->ttl);
    }

}