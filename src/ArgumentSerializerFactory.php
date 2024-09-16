<?php

namespace JesseGall\LaravelMemoize;

use Closure;
use Illuminate\Database\Eloquent\Model;
use JesseGall\LaravelMemoize\Serializers\ClosureSerializer;
use JesseGall\LaravelMemoize\Serializers\CompoundTypeSerializer;
use JesseGall\LaravelMemoize\Serializers\GenericSerializer;
use JesseGall\LaravelMemoize\Serializers\ModelSerializer;
use JesseGall\LaravelMemoize\Serializers\SerializerInterface;

class ArgumentSerializerFactory implements ArgumentSerializerFactoryInterface
{

    public function make(mixed $arg): SerializerInterface
    {
        return match (true) {
            $arg instanceof Model => new ModelSerializer,
            $arg instanceof Closure => new ClosureSerializer,
            is_object($arg) || is_array($arg) => new CompoundTypeSerializer,
            default => new GenericSerializer
        };
    }

}