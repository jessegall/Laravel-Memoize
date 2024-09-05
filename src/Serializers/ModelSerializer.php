<?php

namespace JesseGall\LaravelMemoize\Serializers;

use Illuminate\Database\Eloquent\Model;

/**
 * @implements Serializer<Model>
 */
class ModelSerializer implements Serializer
{

    public function serialize(mixed $value): string
    {
        return get_class($value) . ':' . $value->getKey();
    }

}