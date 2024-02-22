<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Business\Hyperf\Utils\Collection;

/**
 * Get an item from an array or object using "dot" notation.
 *
 * @param mixed $target
 * @param null|array|int|string $key
 * @param mixed $default
 * @return mixed
 */
function data_get($target, $key, $default = null)
{
    $key = is_array($key) ? $key : explode('.', is_int($key) ? (string) $key : $key);
    return \Hyperf\Collection\data_get($target, $key, $default);
}
