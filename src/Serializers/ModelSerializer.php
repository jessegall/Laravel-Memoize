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
        if (! $value->getKey()) {
            throw new ModelHasNoKey();
        }

        return get_class($value) . ':' . $value->getKey();
    }

}