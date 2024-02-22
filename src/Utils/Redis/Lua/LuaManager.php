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

namespace Business\Hyperf\Utils\Redis\Lua;

use Hyperf\Context\ApplicationContext;
use Business\Hyperf\Utils\Redis\Lua\Contracts\LuaInterface;
use Business\Hyperf\Utils\Redis\Lua\Exception\OperatorNotFoundException;
use Business\Hyperf\Utils\Redis\Lua\Contracts\OperatorInterface;
use Business\Hyperf\Utils\Redis\Lua\Contracts\BatchFuzzyDelete;

use Hyperf\Redis\RedisFactory;

class LuaManager implements LuaInterface
{
    /**
     * @var array<string,OperatorInterface>
     */
    protected $operators = [];

    /**
     * @var array<string,string>
     */
    protected $luaShas = [];

    public function __construct()
    {
    }

    public function handle(string $key, array $keys, ?string $poolName = 'default', ?int $num = null)
    {
        $sha = $this->getLuaSha($key,$poolName);

        $operator = $this->getOperator($key);

        if ($num === null) {
            $num = count($keys);
        }

        $redis = ApplicationContext::getContainer()->get(RedisFactory::class)->get($poolName);
        if (! empty($sha)) {
            $luaData = $redis->evalSha($sha, $keys, $num);
        } else {
            $script = $operator->getScript();
            $luaData = $redis->eval($script, $keys, $num);
        }

        return $operator->parseResponse($luaData);
    }

    public function getOperator(string $key): OperatorInterface
    {
        if (! isset($this->operators[$key])) {
            $this->operators[$key] = make($key);
        }

        if (! $this->operators[$key] instanceof OperatorInterface) {
            throw new OperatorNotFoundException(sprintf('The operator %s is not instanceof OperatorInterface.', $key));
        }

        return $this->operators[$key];
    }

    public function getLuaSha(string $key, string $poolName = 'default'): string
    {
        if (empty($this->luaShas[$poolName][$key])) {
            $this->luaShas[$poolName][$key] = ApplicationContext::getContainer()->get(RedisFactory::class)->get($poolName)->script('load', $this->getOperator($key)->getScript());
        }
        return $this->luaShas[$poolName][$key];
    }

    public function batchFuzzyDelete(string $key, string $poolName = 'default')
    {
        return $this->handle(BatchFuzzyDelete::class, [$key], $poolName);
    }
}
