<?php

namespace JesseGall\LaravelMemoize\Serializers;

class GenericSerializer implements SerializerInterface
{

    public function serialize(mixed $value): string
    {
        return serialize($value);
    }

}