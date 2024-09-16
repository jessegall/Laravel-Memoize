<?php

namespace JesseGall\LaravelMemoize\Serializers;

/**
 * @template T
 */
interface SerializerInterface
{

    /**
     * @param T $value
     * @return string
     */
    public function serialize(mixed $value): string;

}