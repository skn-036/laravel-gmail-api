<?php

namespace Skn036\Gmail\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Skn036\Gmail\Gmail
 */
class Gmail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Skn036\Gmail\Gmail::class;
    }
}
