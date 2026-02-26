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

namespace Hyperf\Cache\Helper;

use Hyperf\Stringable\Str;

use function Hyperf\Collection\data_get;

class StringHelper
{
    /**
     * Format cache key with prefix and arguments.
     */
    public static function format(string $prefix, array $arguments, ?string $value = null): string
    {
        $arguments = StringHelper::handleArguments($arguments);

        if ($value !== null) {
            if ($matches = StringHelper::parse($value)) {
                foreach ($matches as $search) {
                    $k = str_replace(['#{', '}'], '', $search);

                    $value = Str::replaceFirst($search, (string) data_get($arguments, $k), $value);
                }
            }
        } else {
            $value = implode(':', $arguments);
        }

        return $prefix . ':' . $value;
    }

    /**
     * Parse expression of value.
     */
    public static function parse(string $value): array
    {
        preg_match_all('/#\{[\w.]+}/', $value, $matches);

        return $matches[0];
    }

    /**
     * handle of arguments.
     */
    public static function handleArguments(array $arguments): array
    {
        foreach ($arguments as $k => $v) {

            if (is_array($v)) {
                $result = [];
                array_walk_recursive($v, function ($item) use (&$result) {

                    if (is_array($item)) {
                        // 将数组转为 JSON 或 print_r，这里用 json_encode 保持可读
                        $item = json_encode($item, JSON_UNESCAPED_UNICODE);
                    } elseif (is_object($item) || is_resource($item)) {
                        $item = serialize($item);
                    }

                    $result[] = $item;
                });

                // 将数组转为 JSON 或 print_r，这里用 json_encode 保持可读
                $v = md5(json_encode($result, JSON_UNESCAPED_UNICODE), true);

            } elseif (is_object($v) || is_resource($v)) {
                $v = md5(serialize($v), true);
            }

            $arguments[$k] = $v;
        }

        return $arguments;
    }
}
