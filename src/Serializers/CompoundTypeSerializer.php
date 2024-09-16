<?php

namespace JesseGall\LaravelMemoize\Serializers;

class CompoundTypeSerializer implements SerializerInterface
{

    public function serialize(mixed $value): string
    {
        return md5(serialize($value));
    }
}