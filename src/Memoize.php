<?php

namespace JesseGall\LaravelMemoize;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use JesseGall\LaravelMemoize\Serializers\ModelSerializer;

trait Memoize
{
    private static array $memoizeCache = [];

    public static function bootMemoize(): void
    {
        if (is_a(static::class, Model::class, true)) {
            foreach (static::memoizeClearCacheOn() as $event) {
                static::registerModelEvent($event, fn(self $model) => $model->memoizeClearCache());
            }
        }
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
        static::$memoizeCache[$this->memoizeTargetKey()] = [];
    }

    public static function memoizeClearStaticCache(): void
    {
        static::$memoizeCache = [];
    }

    public function memoizeGetCache(): array
    {
        return static::$memoizeCache[$this->memoizeTargetKey()] ?? [];
    }

    private function memoize(Closure $callback): mixed
    {
        return self::$memoizeCache[$this->memoizeTargetKey()][$this->memoizeMethodKey()] ??= $callback();
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