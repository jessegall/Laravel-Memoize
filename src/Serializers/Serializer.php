<?php

namespace JesseGall\LaravelMemoize\Serializers;

/**
 * @template T
 */
interface Serializer
{

    /**
     * @param T $value
     * @return string
     */
    public function serialize(mixed $value): string;

}