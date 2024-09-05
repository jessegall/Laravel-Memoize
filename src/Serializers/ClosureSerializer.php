<?php

namespace JesseGall\LaravelMemoize\Serializers;

use Closure;

/**
 * @implements Serializer<Closure>
 */
class ClosureSerializer implements Serializer
{

    /**
     * @throws \ReflectionException
     */
    public function serialize(mixed $value): string
    {
        $reflection = new \ReflectionFunction($value);

        return $reflection->getFileName() . ':' . $reflection->getStartLine();
    }

}