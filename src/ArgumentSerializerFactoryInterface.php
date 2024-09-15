<?php

namespace JesseGall\LaravelMemoize;

use JesseGall\LaravelMemoize\Serializers\Serializer;

interface ArgumentSerializerFactoryInterface
{

    public function make(mixed $arg): Serializer;

}