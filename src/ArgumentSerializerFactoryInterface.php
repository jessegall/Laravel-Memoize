<?php

namespace JesseGall\LaravelMemoize;

use JesseGall\LaravelMemoize\Serializers\SerializerInterface;

interface ArgumentSerializerFactoryInterface
{

    public function make(mixed $arg): SerializerInterface;

}