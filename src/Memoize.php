<?php

namespace JesseGall\LaravelMemoize;

use Closure;
use Illuminate\Database\Eloquent\Model;
use JesseGall\LaravelMemoize\Serializers\ClosureSerializer;
use JesseGall\LaravelMemoize\Serializers\GenericSerializer;
use JesseGall\LaravelMemoize\Serializers\ModelSerializer;
use JesseGall\LaravelMemoize\Serializers\Serializer;

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
        self::$memoizeCache[$this->memoizeTargetKey()] = [];
    }

    public static function memoizeClearStaticCache(): void
    {
        self::$memoizeCache = [];
    }

    public function memoizeGetCache(): array
    {
        return self::$memoizeCache[$this->memoizeTargetKey()] ?? [];
    }

    private function memoize(Closure $callback): mixed
    {
        return self::$memoizeCache[$this->memoizeTargetKey()][$this->memoizeMethodKey()] ??= $callback();
    }

    private function memoizeTargetKey(): string
    {
        if ($this instanceof Model) {
            $key = $this->getKey();

            if (! $key) {
                throw new ModelHasNoKey();
            }

            return static::class . ':' . $this->getKey();
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
        $args = array_map(
            fn(mixed $arg) => $this->memoizeGetArgumentSerializer($arg)->serialize($arg),
            $args
        );

        return implode(':', $args);
    }

    private function memoizeGetArgumentSerializer(mixed $arg): Serializer
    {
        return match (true) {
            $arg instanceof Model => new ModelSerializer(),
            $arg instanceof Closure => new ClosureSerializer(),
            default => new GenericSerializer()
        };
    }

}