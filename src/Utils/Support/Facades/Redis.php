<?php

namespace Business\Hyperf\Utils\Support\Facades;

use Hyperf\Context\ApplicationContext;
use Hyperf\Redis\RedisFactory;

class Redis
{
    /**
     * 获取redis连接
     * @param string $poolName 连接池
     * @return \Hyperf\Redis\RedisProxy
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public static function getRedis(string $poolName = 'default')
    {
        $container = ApplicationContext::getContainer();
        $redisFactory = $container->get(RedisFactory::class);

        $db = config('redis.' . $poolName . '.db');
        $redis = $redisFactory->get($poolName);
        $redis->select($db);

        return $redis;
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        return static::getRedis()->{$method}(...$args);
    }

    /**
     * Adds a values to the set value stored at key. 添加集合元素 支持设置缓存时长
     *
     * @param $key 集合key
     * @param array $value 集合元素
     * @param int|null $seconds 缓存时间  单位秒(支持：0.02)
     * @param string $poolName 连接池
     * @return array|bool|int The number of elements added to the set.
     * If this value is already in the set, FALSE is returned
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     *
     * @link    https://redis.io/commands/sadd
     * @example
     * <pre>
     * $redis->sAdd('k', 'v1');                // int(1)
     * $redis->sAdd('k', 'v1', 'v2', 'v3');    // int(2)
     * </pre>
     */
    public static function sAdd($key, array $value, int $seconds = null, string $poolName = 'default')
    {
        $instance = static::getRedis($poolName);

        if ($seconds === null) {
            return $instance->sAdd($key, ...$value);
        }

        $instance->multi();
        $instance->sAdd($key, ...$value);
        $instance->pexpire($key, $seconds * 1000);
        $manyResult = $instance->exec();

        return [
            'result' => data_get($manyResult, 0),
            'pexpire' => data_get($manyResult, 1),
        ];
    }

    /**
     * Fills in a whole hash. Non-string values are converted to string, using the standard (string) cast.
     * NULL values are stored as empty strings
     *
     * @param $key
     * @param array $dictionary key → value array
     * @param int|null $seconds 缓存时间  单位秒(支持：0.02)
     * @param string $poolName 连接池
     * @return array|bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     *
     * @link    https://redis.io/commands/hmset
     * @example
     * <pre>
     * $redis->del('user:1');
     * $redis->hMSet('user:1', array('name' => 'Joe', 'salary' => 2000));
     * $redis->hIncrBy('user:1', 'salary', 100); // Joe earns 100 more now.
     * </pre>
     */
    public static function hmset($key, array $dictionary, int $seconds = null, string $poolName = 'default')
    {
        $instance = static::getRedis($poolName);

        if ($seconds === null) {
            return $instance->hmset($key, $dictionary);
        }

        $instance->multi();
        $instance->hmset($key, $dictionary);
        $instance->pexpire($key, $seconds * 1000);
        $manyResult = $instance->exec();

        return [
            'result' => data_get($manyResult, 0),
            'pexpire' => data_get($manyResult, 1),
        ];
    }

    /**
     * Increments the value of a member from a hash by a given amount.
     *
     * @param string $key
     * @param string $hashKey
     * @param int    $value (integer) value that will be added to the member's value
     * @param int|null $seconds 缓存时间  单位秒(支持：0.02)
     * @param string $poolName 连接池
     *
     * @return array|int the new value
     *
     * @link    https://redis.io/commands/hincrby
     * @example
     * <pre>
     * $redis->del('h');
     * $redis->hIncrBy('h', 'x', 2); // returns 2: h[x] = 2 now.
     * $redis->hIncrBy('h', 'x', 1); // h[x] ← 2 + 1. Returns 3
     * </pre>
     */
    public static function hIncrBy($key, $hashKey, $value, int $seconds = null, string $poolName = 'default')
    {
        $instance = static::getRedis($poolName);

        if ($seconds === null) {
            return $instance->hIncrBy($key, $hashKey, $value);
        }

        $instance->multi();
        $instance->hIncrBy($key, $hashKey, $value);
        $instance->pexpire($key, $seconds * 1000);
        $manyResult = $instance->exec();

        return [
            'result' => data_get($manyResult, 0),
            'pexpire' => data_get($manyResult, 1),
        ];
    }

    /**
     * Adds the specified member with a given score to the sorted set stored at key
     *
     * @param string       $key     Required key
     * @param array        $options Options if needed
     * @param float        $score1  Required score
     * @param string|mixed $value1  Required value
     * @param float        $score2  Optional score
     * @param string|mixed $value2  Optional value
     * @param float        $scoreN  Optional score
     * @param string|mixed $valueN  Optional value
     *
     * @return int Number of values added
     *
     * @link    https://redis.io/commands/zadd
     * @example
     * <pre>
     * <pre>
     * $redis->zAdd('z', 1, 'v1', 2, 'v2', 3, 'v3', 4, 'v4' );  // int(2)
     * $redis->zRem('z', 'v2', 'v3');                           // int(2)
     * $redis->zAdd('z', ['NX'], 5, 'v5');                      // int(1)
     * $redis->zAdd('z', ['NX'], 6, 'v5');                      // int(0)
     * $redis->zAdd('z', 7, 'v6');                              // int(1)
     * $redis->zAdd('z', 8, 'v6');                              // int(0)
     *
     * var_dump( $redis->zRange('z', 0, -1) );
     * // Output:
     * // array(4) {
     * //   [0]=> string(2) "v1"
     * //   [1]=> string(2) "v4"
     * //   [2]=> string(2) "v5"
     * //   [3]=> string(2) "v8"
     * // }
     *
     * var_dump( $redis->zRange('z', 0, -1, true) );
     * // Output:
     * // array(4) {
     * //   ["v1"]=> float(1)
     * //   ["v4"]=> float(4)
     * //   ["v5"]=> float(5)
     * //   ["v6"]=> float(8)
     * </pre>
     * </pre>
     */
    public static function zAdd($key, array $value, int $seconds = null, string $poolName = 'default')
    {
        $instance = static::getRedis($poolName);

        if ($seconds === null) {
            return $instance->zAdd($key, ...$value);
        }

        $instance->multi();
        $instance->zAdd($key, ...$value);
        $instance->pexpire($key, $seconds * 1000);
        $manyResult = $instance->exec();

        return [
            'result' => data_get($manyResult, 0),
            'pexpire' => data_get($manyResult, 1),
        ];
    }

    /**
     * Increment the number stored at key by one.
     * If the second argument is filled, it will be used as the integer value of the increment.
     *
     * @param string $key   key
     * @param int    $value value that will be added to key (only for incrBy)
     *
     * @return int the new value
     *
     * @link    https://redis.io/commands/incrby
     * @example
     * <pre>
     * $redis->incr('key1');        // key1 didn't exists, set to 0 before the increment and now has the value 1
     * $redis->incr('key1');        // 2
     * $redis->incr('key1');        // 3
     * $redis->incr('key1');        // 4
     * $redis->incrBy('key1', 10);  // 14
     * </pre>
     */
    public static function incrBy($key, $value, int $seconds = null, string $poolName = 'default')
    {
        $instance = static::getRedis($poolName);

        if ($seconds === null) {
            return $instance->incrBy($key, $value);
        }

        $instance->multi();
        $instance->incrBy($key, $value);
        $instance->pexpire($key, $seconds * 1000);
        $manyResult = $instance->exec();

        return [
            'result' => data_get($manyResult, 0),
            'pexpire' => data_get($manyResult, 1),
        ];
    }

}
