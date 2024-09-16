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
            foreach (static::memoizeCacheInvalidationEvents() as $event) {
                static::registerModelEvent($event, fn(self $model) => $model->memoizeForget());
            }
        }
    }

    public static function memoizeDriver(): DriverInterface
    {
        return new MemoryDriver();
    }

    public static function memoizeCacheInvalidationEvents(): array
    {
        return [
            'saved',
            'deleted',
        ];
    }

    public function memoizeForget(): void
    {
        $this->memoizeDriver()->forget($this->memoizeTargetKey());
    }

    public function memoizeGet(): array
    {
        return self::memoizeDriver()->get($this->memoizeTargetKey());
    }

    private function memoize(Closure $callback): mixed
    {
        $driver = static::memoizeDriver();
        $targetKey = $this->memoizeTargetKey();
        $methodKey = $this->memoizeMethodKey();

        if (! $driver->has($targetKey, $methodKey)) {
            $driver->set($targetKey, $methodKey, $callback());
        }

        return $driver->get($targetKey, $methodKey);
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