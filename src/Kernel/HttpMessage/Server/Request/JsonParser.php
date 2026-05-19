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

namespace Business\Hyperf\Kernel\HttpMessage\Server\Request;

use Business\Hyperf\Kernel\Codec\Json;

class JsonParser
{
    /**
     * 解码
     * @param string $rawBody
     * @param bool|null $associative
     * @param int $depth
     * @param int $flags
     * @return mixed
     */
    public static function parse(string $rawBody, ?bool $associative = null, int $depth = 512, int $flags = 0): mixed
    {
        $data = Json::decode($rawBody, $associative, $depth, $flags);
        return $data === null ? $rawBody : $data;
    }
}
