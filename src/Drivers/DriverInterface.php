<?php

namespace JesseGall\LaravelMemoize\Drivers;

interface DriverInterface
{

    public function getAll(): array;

    public function getCacheForTarget(string $targetKey): array;

    public function getCachedMethodValue(string $targetKey, string $methodKey): mixed;

    public function setCachedMethodValue(string $targetKey, string $methodKey, mixed $value): void;

    public function hasCachedMethod(string $targetKey, string $methodKey): bool;

    public function clearAll(): void;

    public function clearTarget(string $targetKey): void;

}