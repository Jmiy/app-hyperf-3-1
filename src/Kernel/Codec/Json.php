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

namespace Business\Hyperf\Kernel\Codec;

use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;
use Throwable;

class Json
{
    /**
     * 编码
     * @param mixed $data
     * @param int $flags
     * @param int $depth
     * @return string|false
     */
    public static function encode(mixed $data, int $flags = JSON_UNESCAPED_UNICODE, int $depth = 512): string|false
    {
        if ($data instanceof Jsonable) {
            return (string)$data;
        }

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        return json_encode($data, $flags, $depth);
    }

    /**
     * 解码
     * @param string $json
     * @param bool|null $associative
     * @param int $depth
     * @param int $flags
     * @return mixed
     */
    public static function decode(string $json, ?bool $associative = null, int $depth = 512, int $flags = 0): mixed
    {
        if (function_exists('json_validate')) {
            if (!json_validate($json)) {
                return null;
            }
        }

        return json_decode($json, $associative, $depth, $flags);
    }
}
