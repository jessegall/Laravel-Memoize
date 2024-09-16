<?php

namespace JesseGall\LaravelMemoize\Drivers;

class CacheDriver implements DriverInterface
{

    public function __construct(
        private readonly int $ttl = 60,
    ) {}

    public function get(string $target = null, string $method = null): mixed
    {
        if ($target === null) {
            return array_map(fn(string $target) => $this->get($target), $this->targets());
        }

        if ($method === null) {
            return array_map(fn(string $method) => $this->get($target, $method), $this->methods($target));
        }

        return cache()->get($this->targetMethodKey($target, $method));
    }

    public function set(string $target, string $method, mixed $value): void
    {
        cache()->put($this->targetKey($target), [...$this->methods($target), $method], $this->ttl);

        cache()->put($this->targetMethodKey($target, $method), $value, $this->ttl);
    }

    public function has(string $target, string $method): bool
    {
        return cache()->has($this->targetMethodKey($target, $method));
    }

    public function forget(string $target = null): void
    {
        $targets = $target === null ? $this->targets() : [$target];

        foreach ($targets as $target) {
            $targetKey = $this->targetKey($target);
            $methods = cache()->get($targetKey, []);
            foreach ($methods as $method) {
                cache()->forget($this->targetMethodKey($target, $method));
            }
            cache()->forget($targetKey);
        }
    }

    protected function key(): string
    {
        return self::class;
    }

    protected function targetKey(string $target): string
    {
        return $this->key() . ':' . $target;
    }

    protected function targetMethodKey(string $target, string $method): string
    {
        return $this->key() . ':' . $target . ':' . $method;
    }

    private function targets(): array
    {
        return cache()->get($this->key(), []);
    }

    public function methods(string $target): array
    {
        return cache()->get($this->targetKey($target), []);
    }

}