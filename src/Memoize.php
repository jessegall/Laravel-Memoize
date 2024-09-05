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

    private static array $memoizeClearCacheOn = [
        'saved',
        'deleted',
    ];

    public static function bootMemoize(): void
    {
        if (is_a(static::class, Model::class, true)) {
            foreach (self::$memoizeClearCacheOn as $event) {
                static::registerModelEvent($event, fn(self $self) => $self->memoizeClearCache());
            }
        }
    }

    public function memoizeClearCache(bool $static = false): void
    {
        if ($static) {
            self::$memoizeCache = [];
        } else {
            self::$memoizeCache[$this->memoizeTargetKey()] = [];
        }
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
        return $this instanceof Model
            ? static::class . ':' . $this->getKey()
            : static::class;
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