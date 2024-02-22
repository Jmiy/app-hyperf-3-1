<?php

declare(strict_types=1);

namespace Business\Hyperf\Utils;

use Hyperf\Context\Context as HyperfContext;

class Context extends HyperfContext
{
    //protected static $nonCoContext = [];

    public static function storeData($key, callable $callback)
    {
        if (!static::has($key)) {
            return static::set($key, call($callback));
        }

        return static::get($key);
    }
}
