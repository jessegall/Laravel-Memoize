<?php

namespace JesseGall\LaravelMemoize\Serializers;

class GenericSerializer implements Serializer
{

    public function serialize(mixed $value): string
    {
        return serialize($value);
    }

}