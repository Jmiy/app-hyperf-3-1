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
namespace Business\Hyperf\Utils\Redis\Lua\Contracts;
use Business\Hyperf\Utils\Redis\Lua\Contracts\OperatorInterface;

interface LuaInterface
{
    public function handle(string $key, array $keys, ?string $poolName = 'default', ?int $num = null);

    public function getOperator(string $key): OperatorInterface;

    public function getLuaSha(string $key, string $poolName = 'default'): string;

    public function batchFuzzyDelete(string $key, string $poolName = 'default');
}
