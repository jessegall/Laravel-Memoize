<?php

namespace JesseGall\LaravelMemoize\Serializers;

use Illuminate\Database\Eloquent\Model;
use JesseGall\LaravelMemoize\ModelHasNoKey;

/**
 * @implements Serializer<Model>
 */
class ModelSerializer implements Serializer
{

    public function serialize(mixed $value): string
    {
        if (! $key = $value->getKey()) {
            throw new ModelHasNoKey();
        }

        return get_class($value) . ':' . $key;
    }

}