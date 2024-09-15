<?php

namespace JesseGall\LaravelMemoize;

use RuntimeException;

class ModelHasNoKey extends RuntimeException
{

    public function __construct()
    {
        parent::__construct("Cannot memoize a model without a key.");
    }

}