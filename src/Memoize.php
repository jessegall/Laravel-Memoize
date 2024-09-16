<?php

namespace JesseGall\LaravelMemoize;

use Closure;
use Illuminate\Database\Eloquent\Model;
use JesseGall\LaravelMemoize\Drivers\DriverInterface;
use JesseGall\LaravelMemoize\Drivers\MemoryDriver;
use JesseGall\LaravelMemoize\Serializers\ModelSerializer;

trait Memoize
{

    public static function bootMemoize(): void
    {
        if (is_a(static::class, Model::class, true)) {
            foreach (static::memoizeClearCacheOn() as $event) {
                static::registerModelEvent($event, fn(self $model) => $model->memoizeClearCache());
            }
        }
    }

    public static function memoizeDriver(): DriverInterface
    {
        return new MemoryDriver();
    }

    public static function memoizeClearCacheOn(): array
    {
        return [
            'saved',
            'deleted',
        ];
    }

    public function memoizeClearCache(): void
    {
        $this->memoizeDriver()->clearTarget($this->memoizeTargetKey());
    }

    public static function memoizeClearStaticCache(): void
    {
        static::memoizeDriver()->clearAll();
    }

    public function memoizeGetCache(): array
    {
        return self::memoizeDriver()->getCacheForTarget($this->memoizeTargetKey());
    }

    private function memoize(Closure $callback): mixed
    {
        $driver = static::memoizeDriver();

        $targetKey = $this->memoizeTargetKey();
        $methodKey = $this->memoizeMethodKey();

        if (! $driver->hasCachedMethod($targetKey, $methodKey)) {
            $driver->setCachedMethodValue($targetKey, $methodKey, $callback());
        }

        return $driver->getCachedMethodValue($targetKey, $methodKey);
    }

    private function memoizeTargetKey(): string
    {
        if ($this instanceof Model) {
            return (new ModelSerializer)->serialize($this);
        }

        return static::class;
    }

    private function memoizeMethodKey(int $backtraceLimit = 3): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $backtraceLimit)[$backtraceLimit - 1];

        return "{$backtrace['function']}:{$this->memoizeSerializeArgs($backtrace['args'])}";
    }

    private function memoizeSerializeArgs(array $args): string
    {
        $factory = $this->memoizeArgumentSerializerFactory();

        $serializedArgs = array_map(fn(mixed $arg) => $factory->make($arg)->serialize($arg), $args);

        return implode(':', $serializedArgs);
    }

    private function memoizeArgumentSerializerFactory(): ArgumentSerializerFactoryInterface
    {
        if (app()->has(ArgumentSerializerFactoryInterface::class)) {
            return app(ArgumentSerializerFactoryInterface::class);
        }

        return new ArgumentSerializerFactory();
    }

}