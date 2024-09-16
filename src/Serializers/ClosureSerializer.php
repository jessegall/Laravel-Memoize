<?php

namespace JesseGall\LaravelMemoize\Serializers;

use Closure;

/**
 * @implements SerializerInterface<Closure>
 */
class ClosureSerializer implements SerializerInterface
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