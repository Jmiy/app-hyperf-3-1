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

namespace Hyperf\Cache\Driver;

use function Hyperf\Collection\data_set;
use function Business\Hyperf\Utils\Collection\data_get;
use Hyperf\Collection\Arr;
use Hyperf\Cache\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Hyperf\Redis\RedisFactory;

class RedisDriver extends Driver implements KeyCollectorInterface
{
    /**
     * @var \Redis
     */
    protected $redis;

    public function __construct(ContainerInterface $container, array $config)
    {
        parent::__construct($container, $config);

        $this->poolName = data_get($config, 'connection', 'default');
        $this->redis = $container->get(RedisFactory::class)->get($this->poolName);

//        $this->redis = $container->get(\Redis::class);
    }

    public function get($key, $default = null): mixed
    {
        $res = $this->redis->get($this->getCacheKey($key));
        if ($res === false) {
            return $default;
        }

        return $this->packer->unpack($res);
    }

    public function fetch(string $key, $default = null): array
    {
        $res = $this->redis->get($this->getCacheKey($key));
        if ($res === false) {
            return [false, $default];
        }

        return [true, $this->packer->unpack($res)];
    }

    public function set($key, $value, $ttl = null): bool
    {
        $seconds = $this->secondsUntil($ttl);
        $res = $this->packer->pack($value);
        if ($seconds > 0) {
            return $this->redis->set($this->getCacheKey($key), $res, $seconds);
        }

        return $this->redis->set($this->getCacheKey($key), $res);
    }

    public function delete($key): bool
    {
        return (bool)$this->redis->del($this->getCacheKey($key));
    }

    public function clear(): bool
    {
        return $this->clearPrefix('');
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $cacheKeys = array_map(function ($key) {
            return $this->getCacheKey($key);
        }, $keys);

        $values = $this->redis->mget($cacheKeys);
        $result = [];
        foreach ($keys as $i => $key) {
            $result[$key] = $values[$i] === false ? $default : $this->packer->unpack($values[$i]);
        }

        return $result;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        if (!is_array($values)) {
            throw new InvalidArgumentException('The values is invalid!');
        }

        $cacheKeys = [];
        foreach ($values as $key => $value) {
            $cacheKeys[$this->getCacheKey($key)] = $this->packer->pack($value);
        }

        $seconds = $this->secondsUntil($ttl);
        if ($seconds > 0) {
            foreach ($cacheKeys as $key => $value) {
                $this->redis->set($key, $value, $seconds);
            }

            return true;
        }

        return $this->redis->mset($cacheKeys);
    }

    public function deleteMultiple($keys): bool
    {
        $cacheKeys = array_map(function ($key) {
            return $this->getCacheKey($key);
        }, $keys);

        return (bool)$this->redis->del(...$cacheKeys);
    }

    public function has($key): bool
    {
        return (bool)$this->redis->exists($this->getCacheKey($key));
    }

    public function clearPrefix(string $prefix): bool
    {
        $iterator = null;
        $key = $prefix . '*';
        while (true) {
            $keys = $this->redis->scan($iterator, $this->getCacheKey($key), 10000);
            if (!empty($keys)) {
                $this->redis->del(...$keys);
            }

            if (empty($iterator)) {
                break;
            }
        }

        return true;
    }

    public function addKey(string $collector, string $key): bool
    {
        return (bool)$this->redis->sAdd($this->getCacheKey($collector), $key);
    }

    public function keys(string $collector): array
    {
        return $this->redis->sMembers($this->getCacheKey($collector));
    }

    public function delKey(string $collector, string ...$key): bool
    {
        return (bool)$this->redis->sRem($this->getCacheKey($collector), ...$key);
    }

    public function __call($name, $arguments)
    {
        return $this->redis->{$name}(...$arguments);
    }

    public function handleParameters($_, $index = 0)
    {
        if ($index === 'all') {
            foreach ($_ as $k => $v) {
                data_set($_, (is_int($k) ? (string)$k : $k), $this->getCacheKey($v));
            }
            return $_;
        }

        $index = is_array($index) ? $index : [$index];
        foreach ($index as $k) {
            data_set($_, (is_int($k) ? (string)$k : $k), $this->getCacheKey(data_get($_, $k)));
        }

        return $_;
    }

    public function handleArrayParameters(array $dictionary)
    {
        $_ = [];
        foreach ($dictionary as $k => $v) {
            $_[$this->getCacheKey($k)] = $v;
        }
        return $_;
    }

    public function del($keys)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function dump($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args(), 'all'));
    }

    /**
     * Verify if the specified key/keys exists
     *
     * This function took a single argument and returned TRUE or FALSE in phpredis versions < 4.0.0.
     *
     * @param string|string[] $key
     *
     * @return int|bool The number of keys tested that do exist
     *
     * @since >= 4.0 Returned int, if < 4.0 returned bool
     *
     * @link https://redis.io/commands/exists
     * @link https://github.com/phpredis/phpredis#exists
     * @example
     * <pre>
     * $redis->exists('key'); // 1
     * $redis->exists('NonExistingKey'); // 0
     *
     * $redis->mset(['foo' => 'foo', 'bar' => 'bar', 'baz' => 'baz']);
     * $redis->exists(['foo', 'bar', 'baz]); // 3
     * $redis->exists('foo', 'bar', 'baz'); // 3
     * </pre>
     */
    public function exists(...$key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args(), 'all'));
    }

    /**
     * Sets an expiration date (a timeout) on an item
     *
     * @param string $key The key that will disappear
     * @param int $ttl The key's remaining Time To Live, in seconds
     *
     * @return bool TRUE in case of success, FALSE in case of failure
     *
     * @link    https://redis.io/commands/expire
     * @example
     * <pre>
     * $redis->set('x', '42');
     * $redis->expire('x', 3);  // x will disappear in 3 seconds.
     * sleep(5);                    // wait 5 seconds
     * $redis->get('x');            // will return `FALSE`, as 'x' has expired.
     * </pre>
     */
    public function expire($key, $seconds)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function expireat($key, $timestamp)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function cacheKeys($pattern)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function move($key, $db)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function object($subcommand, $key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args(), 1));
    }

    public function persist($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Sets an expiration date (a timeout in milliseconds) on an item
     *
     * @param string $key The key that will disappear.
     * @param int $ttl The key's remaining Time To Live, in milliseconds
     *
     * @return bool TRUE in case of success, FALSE in case of failure
     *
     * @link    https://redis.io/commands/pexpire
     * @example
     * <pre>
     * $redis->set('x', '42');
     * $redis->pExpire('x', 11500); // x will disappear in 11500 milliseconds.
     * $redis->ttl('x');            // 12
     * $redis->pttl('x');           // 11500
     * </pre>
     */
    public function pexpire($key, $milliseconds)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Sets an expiration date (a timestamp) on an item. Requires a timestamp in milliseconds
     *
     * @param string $key The key that will disappear
     * @param int $timestamp Unix timestamp. The key's date of death, in seconds from Epoch time
     *
     * @return bool TRUE in case of success, FALSE in case of failure
     *
     * @link    https://redis.io/commands/pexpireat
     * @example
     * <pre>
     * $redis->set('x', '42');
     * $redis->pexpireat('x', 1555555555005);
     * echo $redis->ttl('x');                       // 218270121
     * echo $redis->pttl('x');                      // 218270120575
     * </pre>
     */
    public function pexpireat($key, $timestamp)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function pttl($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function randomkey()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function rename($key, $target)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args(), 'all'));
    }

    public function renamenx($key, $target)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args(), 'all'));
    }

    /**
     * Scan a set for members
     *
     * @param string $key The set to search.
     * @param int $iterator LONG (reference) to the iterator as we go.
     * @param string $pattern String, optional pattern to match against.
     * @param int $count How many members to return at a time (Redis might return a different amount)
     *
     * @return array|bool PHPRedis will return an array of keys or FALSE when we're done iterating
     *
     * @link    https://redis.io/commands/sscan
     * @example
     * <pre>
     * $iterator = null;
     * while ($members = $redis->sScan('set', $iterator)) {
     *     foreach ($members as $member) {
     *         echo $member . PHP_EOL;
     *     }
     * }
     * </pre>
     */
    public function scan($key, &$iterator, $pattern = null, $count = 0)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Sort
     *
     * @param string $key
     * @param array $option array(key => value, ...) - optional, with the following keys and values:
     * - 'by' => 'some_pattern_*',
     * - 'limit' => array(0, 1),
     * - 'get' => 'some_other_pattern_*' or an array of patterns,
     * - 'sort' => 'asc' or 'desc',
     * - 'alpha' => TRUE,
     * - 'store' => 'external-key'
     *
     * @return array
     * An array of values, or a number corresponding to the number of elements stored if that was used
     *
     * @link    https://redis.io/commands/sort
     * @example
     * <pre>
     * $redis->del('s');
     * $redis->sadd('s', 5);
     * $redis->sadd('s', 4);
     * $redis->sadd('s', 2);
     * $redis->sadd('s', 1);
     * $redis->sadd('s', 3);
     *
     * var_dump($redis->sort('s')); // 1,2,3,4,5
     * var_dump($redis->sort('s', array('sort' => 'desc'))); // 5,4,3,2,1
     * var_dump($redis->sort('s', array('sort' => 'desc', 'store' => 'out'))); // (int)5
     * </pre>
     */
    public function sort($key, $option = null)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function ttl($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function type($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function append($key, $value)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Count bits in a string
     *
     * @param string $key
     *
     * @return int The number of bits set to 1 in the value behind the input key
     *
     * @link    https://redis.io/commands/bitcount
     * @example
     * <pre>
     * $redis->set('bit', '345'); // // 11 0011  0011 0100  0011 0101
     * var_dump( $redis->bitCount('bit', 0, 0) ); // int(4)
     * var_dump( $redis->bitCount('bit', 1, 1) ); // int(3)
     * var_dump( $redis->bitCount('bit', 2, 2) ); // int(4)
     * var_dump( $redis->bitCount('bit', 0, 2) ); // int(11)
     * </pre>
     */
    public function bitcount($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Bitwise operation on multiple keys.
     *
     * @param string $operation either "AND", "OR", "NOT", "XOR"
     * @param string $retKey return key
     * @param string $key1 first key
     * @param string ...$otherKeys variadic list of keys
     *
     * @return int The size of the string stored in the destination key
     *
     * @link    https://redis.io/commands/bitop
     * @example
     * <pre>
     * $redis->set('bit1', '1'); // 11 0001
     * $redis->set('bit2', '2'); // 11 0010
     *
     * $redis->bitOp('AND', 'bit', 'bit1', 'bit2'); // bit = 110000
     * $redis->bitOp('OR',  'bit', 'bit1', 'bit2'); // bit = 110011
     * $redis->bitOp('NOT', 'bit', 'bit1', 'bit2'); // bit = 110011
     * $redis->bitOp('XOR', 'bit', 'bit1', 'bit2'); // bit = 11
     * </pre>
     */
    public function bitop($operation, $retKey, $key1, ...$otherKeys)
    {
        $args = func_get_args();
        $operation = array_shift($args);
        $args = Arr::collapse([[$operation], $this->handleParameters($args, 'all')]);
        return $this->__call(__FUNCTION__, $args);
    }

    public function bitfield($key, $subcommand, ...$subcommandArg)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function bitpos($key, $bit, $start = null, $end = null)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function decr($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function decrby($key, $decrement)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function getbit($key, $offset)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function getrange($key, $start, $end)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function getset($key, $value)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function incr($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function incrby($key, $increment)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function incrbyfloat($key, $increment)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function mget(array $keys)
    {
        $args = $this->handleParameters($keys, 'all');
        return $this->__call(__FUNCTION__, [$args]);
    }

    public function mset(array $dictionary)
    {
        return $this->__call(__FUNCTION__, [$this->handleArrayParameters($dictionary)]);
    }

    public function msetnx(array $dictionary)
    {
        return $this->__call(__FUNCTION__, [$this->handleArrayParameters($dictionary)]);
    }

    public function psetex($key, $milliseconds, $value)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Changes a single bit of a string.
     *
     * @param string $key
     * @param int $offset
     * @param bool|int $value bool or int (1 or 0)
     * @param int $seconds 缓存时间  单位秒(支持：0.02)
     *
     * @return int 0 or 1, the value of the bit before it was set
     *
     * @link    https://redis.io/commands/setbit
     * @example
     * <pre>
     * $redis->set('key', "*");     // ord("*") = 42 = 0x2f = "0010 1010"
     * $redis->setBit('key', 5, 1); // returns 0
     * $redis->setBit('key', 7, 1); // returns 0
     * $redis->get('key');          // chr(0x2f) = "/" = b("0010 1111")
     * </pre>
     */
    public function setbit($key, $offset, $value, $seconds = null)
    {
        $instance = $this->redis;

        $key = $this->getCacheKey($key);

        if ($seconds !== null) {

            $instance->multi();

            $instance->setbit($key, $offset, $value);

            $instance->pexpire($key, $seconds * 1000);

            $manyResult = $instance->exec();

            $result = [
                'result' => data_get($manyResult, 0),
                'pexpire' => data_get($manyResult, 1),
            ];

        } else {
            $result = $instance->setbit($key, $offset, $value);
        }

        return $result;
    }

    public function setex($key, $seconds, $value)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setnx($key, $value)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function setrange($key, $offset, $value)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function strlen($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Removes a values from the hash stored at key.
     * If the hash table doesn't exist, or the key doesn't exist, FALSE is returned.
     *
     * @param string $key
     * @param string $hashKey1
     * @param string ...$otherHashKeys
     *
     * @return int|bool Number of deleted fields
     *
     * @link    https://redis.io/commands/hdel
     * @example
     * <pre>
     * $redis->hMSet('h',
     *               array(
     *                    'f1' => 'v1',
     *                    'f2' => 'v2',
     *                    'f3' => 'v3',
     *                    'f4' => 'v4',
     *               ));
     *
     * var_dump( $redis->hDel('h', 'f1') );        // int(1)
     * var_dump( $redis->hDel('h', 'f2', 'f3') );  // int(2)
     * s
     * var_dump( $redis->hGetAll('h') );
     * //// Output:
     * //  array(1) {
     * //    ["f4"]=> string(2) "v4"
     * //  }
     * </pre>
     */
    public function hdel($key, array $fields)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Verify if the specified member exists in a key.
     *
     * @param string $key
     * @param string $hashKey
     *
     * @return bool If the member exists in the hash table, return TRUE, otherwise return FALSE.
     *
     * @link    https://redis.io/commands/hexists
     * @example
     * <pre>
     * $redis->hSet('h', 'a', 'x');
     * $redis->hExists('h', 'a');               //  TRUE
     * $redis->hExists('h', 'NonExistingKey');  // FALSE
     * </pre>
     */
    public function hexists($key, $field)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Gets a value from the hash stored at key.
     * If the hash table doesn't exist, or the key doesn't exist, FALSE is returned.
     *
     * @param string $key
     * @param string $hashKey
     *
     * @return string The value, if the command executed successfully BOOL FALSE in case of failure
     *
     * @link    https://redis.io/commands/hget
     */
    public function hget($key, $field)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Returns the whole hash, as an array of strings indexed by strings.
     *
     * @param string $key
     *
     * @return array An array of elements, the contents of the hash.
     *
     * @link    https://redis.io/commands/hgetall
     * @example
     * <pre>
     * $redis->del('h');
     * $redis->hSet('h', 'a', 'x');
     * $redis->hSet('h', 'b', 'y');
     * $redis->hSet('h', 'c', 'z');
     * $redis->hSet('h', 'd', 't');
     * var_dump($redis->hGetAll('h'));
     *
     * // Output:
     * // array(4) {
     * //   ["a"]=>
     * //   string(1) "x"
     * //   ["b"]=>
     * //   string(1) "y"
     * //   ["c"]=>
     * //   string(1) "z"
     * //   ["d"]=>
     * //   string(1) "t"
     * // }
     * // The order is random and corresponds to redis' own internal representation of the set structure.
     * </pre>
     */
    public function hgetall($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Increments the value of a member from a hash by a given amount.
     *
     * @param string $key
     * @param string $hashKey
     * @param int $value (integer) value that will be added to the member's value
     *
     * @return int the new value
     *
     * @link    https://redis.io/commands/hincrby
     * @example
     * <pre>
     * $redis->del('h');
     * $redis->hIncrBy('h', 'x', 2); // returns 2: h[x] = 2 now.
     * $redis->hIncrBy('h', 'x', 1); // h[x] ← 2 + 1. Returns 3
     * </pre>
     */
    public function hincrby($key, $field, $increment)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function hmincrby($key, array $values)
    {
        $instance = $this->redis;
        //$instance->select('1');
        $key = $this->getCacheKey($key);

        $instance->multi();

        $_manyResult = [];
        foreach ($values as $field => $increment) {
            $instance->hincrby($key, $field, $increment);
            $_manyResult[] = $field;
        }
        $exeResult = $instance->exec();

        $manyResult = [];
        foreach ($exeResult as $index => $result) {
            $manyResult[data_get($_manyResult, $index)] = $result;
        }

        unset($_manyResult);

        return $manyResult;
    }

    /**
     * Increment the float value of a hash field by the given amount
     *
     * @param string $key
     * @param string $field
     * @param float $increment
     *
     * @return float
     *
     * @link    https://redis.io/commands/hincrbyfloat
     * @example
     * <pre>
     * $redis = new Redis();
     * $redis->connect('127.0.0.1');
     * $redis->hset('h', 'float', 3);
     * $redis->hset('h', 'int',   3);
     * var_dump( $redis->hIncrByFloat('h', 'float', 1.5) ); // float(4.5)
     *
     * var_dump( $redis->hGetAll('h') );
     *
     * // Output
     *  array(2) {
     *    ["float"]=>
     *    string(3) "4.5"
     *    ["int"]=>
     *    string(1) "3"
     *  }
     * </pre>
     */
    public function hincrbyfloat($key, $field, $increment)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Returns the keys in a hash, as an array of strings.
     *
     * @param string $key
     *
     * @return array An array of elements, the keys of the hash. This works like PHP's array_keys().
     *
     * @link    https://redis.io/commands/hkeys
     * @example
     * <pre>
     * $redis->del('h');
     * $redis->hSet('h', 'a', 'x');
     * $redis->hSet('h', 'b', 'y');
     * $redis->hSet('h', 'c', 'z');
     * $redis->hSet('h', 'd', 't');
     * var_dump($redis->hKeys('h'));
     *
     * // Output:
     * // array(4) {
     * // [0]=>
     * // string(1) "a"
     * // [1]=>
     * // string(1) "b"
     * // [2]=>
     * // string(1) "c"
     * // [3]=>
     * // string(1) "d"
     * // }
     * // The order is random and corresponds to redis' own internal representation of the set structure.
     * </pre>
     */
    public function hkeys($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Returns the length of a hash, in number of items
     *
     * @param string $key
     *
     * @return int|bool the number of items in a hash, FALSE if the key doesn't exist or isn't a hash
     *
     * @link    https://redis.io/commands/hlen
     * @example
     * <pre>
     * $redis->del('h')
     * $redis->hSet('h', 'key1', 'hello');
     * $redis->hSet('h', 'key2', 'plop');
     * $redis->hLen('h'); // returns 2
     * </pre>
     */
    public function hlen($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Retirieve the values associated to the specified fields in the hash.
     *
     * @param string $key
     * @param array $hashKeys
     *
     * @return array Array An array of elements, the values of the specified fields in the hash,
     * with the hash keys as array keys.
     *
     * @link    https://redis.io/commands/hmget
     * @example
     * <pre>
     * $redis->del('h');
     * $redis->hSet('h', 'field1', 'value1');
     * $redis->hSet('h', 'field2', 'value2');
     * $redis->hmGet('h', array('field1', 'field2')); // returns array('field1' => 'value1', 'field2' => 'value2')
     * </pre>
     */
    public function hmget($key, array $fields)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Fills in a whole hash. Non-string values are converted to string, using the standard (string) cast.
     * NULL values are stored as empty strings
     *
     * @param string $key
     * @param array $hashKeys key → value array
     * @param int $seconds 缓存时间  单位秒(支持：0.02)
     *
     * @return bool|array
     *
     * @link    https://redis.io/commands/hmset
     * @example
     * <pre>
     * $redis->del('user:1');
     * $redis->hMSet('user:1', array('name' => 'Joe', 'salary' => 2000));
     * $redis->hIncrBy('user:1', 'salary', 100); // Joe earns 100 more now.
     * </pre>
     */
    public function hmset($key, array $dictionary, $seconds = null)
    {
        $key = $this->getCacheKey($key);
        if ($seconds === null) {
            return $this->__call(__FUNCTION__, [$key, $dictionary]);
        }

        $instance = $this->redis;
        //$instance->select('1');

        $instance->multi();
        $instance->hmset($key, $dictionary);
        $instance->pexpire($key, $seconds * 1000);
        $manyResult = $instance->exec();

//        $connection = $instance->connection('cache');
//
//        $connection->multi();
//
//        $manyResult = $connection->hmset($key, $dictionary);
//
//        if ($seconds !== null) {
//            $result = $connection->pexpire($key, $seconds * 1000);
//            $manyResult = $result && $manyResult;
//        }
//
//        $manyResult = $connection->exec();
//
//        $instance->disconnect();

        return [
            'result' => data_get($manyResult, 0),
            'pexpire' => data_get($manyResult, 1),
        ];
    }

    /**
     * Scan a HASH value for members, with an optional pattern and count.
     *
     * @param string $key
     * @param int $iterator
     * @param string $pattern Optional pattern to match against.
     * @param int $count How many keys to return in a go (only a sugestion to Redis).
     *
     * @return array An array of members that match our pattern.
     *
     * @link    https://redis.io/commands/hscan
     * @example
     * <pre>
     * // $iterator = null;
     * // while($elements = $redis->hscan('hash', $iterator)) {
     * //     foreach($elements as $key => $value) {
     * //         echo $key . ' => ' . $value . PHP_EOL;
     * //     }
     * // }
     * </pre>
     */
    public function hscan($key, $cursor, array $options = null)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Adds a value to the hash stored at key. If this value is already in the hash, FALSE is returned.
     *
     * @param string $key
     * @param string $hashKey
     * @param string $value
     *
     * @return int|bool
     * - 1 if value didn't exist and was added successfully,
     * - 0 if the value was already present and was replaced, FALSE if there was an error.
     *
     * @link    https://redis.io/commands/hset
     * @example
     * <pre>
     * $redis->del('h')
     * $redis->hSet('h', 'key1', 'hello');  // 1, 'key1' => 'hello' in the hash at "h"
     * $redis->hGet('h', 'key1');           // returns "hello"
     *
     * $redis->hSet('h', 'key1', 'plop');   // 0, value was replaced.
     * $redis->hGet('h', 'key1');           // returns "plop"
     * </pre>
     */
    public function hset($key, $field, $value)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Adds a value to the hash stored at key only if this field isn't already in the hash.
     *
     * @param string $key
     * @param string $hashKey
     * @param string $value
     *
     * @return  bool TRUE if the field was set, FALSE if it was already present.
     *
     * @link    https://redis.io/commands/hsetnx
     * @example
     * <pre>
     * $redis->del('h')
     * $redis->hSetNx('h', 'key1', 'hello'); // TRUE, 'key1' => 'hello' in the hash at "h"
     * $redis->hSetNx('h', 'key1', 'world'); // FALSE, 'key1' => 'hello' in the hash at "h". No change since the field
     * wasn't replaced.
     * </pre>
     */
    public function hsetnx($key, $field, $value)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Returns the values in a hash, as an array of strings.
     *
     * @param string $key
     *
     * @return array An array of elements, the values of the hash. This works like PHP's array_values().
     *
     * @link    https://redis.io/commands/hvals
     * @example
     * <pre>
     * $redis->del('h');
     * $redis->hSet('h', 'a', 'x');
     * $redis->hSet('h', 'b', 'y');
     * $redis->hSet('h', 'c', 'z');
     * $redis->hSet('h', 'd', 't');
     * var_dump($redis->hVals('h'));
     *
     * // Output
     * // array(4) {
     * //   [0]=>
     * //   string(1) "x"
     * //   [1]=>
     * //   string(1) "y"
     * //   [2]=>
     * //   string(1) "z"
     * //   [3]=>
     * //   string(1) "t"
     * // }
     * // The order is random and corresponds to redis' own internal representation of the set structure.
     * </pre>
     */
    public function hvals($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Get the string length of the value associated with field in the hash stored at key
     *
     * @param string $key
     * @param string $field
     *
     * @return int the string length of the value associated with field, or zero when field is not present in the hash
     * or key does not exist at all.
     *
     * @link https://redis.io/commands/hstrlen
     * @since >= 3.2
     */
    public function hstrlen($key, $field)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * 移出并获取列表的第一个元素， 如果列表没有元素会阻塞列表直到等待超时或发现可弹出元素为止。
     * Is a blocking lPop primitive. If at least one of the lists contains at least one element,
     * the element will be popped from the head of the list and returned to the caller.
     * Il all the list identified by the keys passed in arguments are empty, blPop will block
     * during the specified timeout until an element is pushed to one of those lists. This element will be popped.
     *
     * @param string|string[] $keys String array containing the keys of the lists OR variadic list of strings
     * @param int $timeout Timeout is always the required final parameter
     *
     * @return array ['listName', 'element']
     *
     * @link    https://redis.io/commands/blpop
     * @example
     * <pre>
     * // Non blocking feature
     * $redis->lPush('key1', 'A');
     * $redis->del('key2');
     *
     * $redis->blPop('key1', 'key2', 10);        // array('key1', 'A')
     * // OR
     * $redis->blPop(['key1', 'key2'], 10);      // array('key1', 'A')
     *
     * $redis->brPop('key1', 'key2', 10);        // array('key1', 'A')
     * // OR
     * $redis->brPop(['key1', 'key2'], 10); // array('key1', 'A')
     *
     * // Blocking feature
     *
     * // process 1
     * $redis->del('key1');
     * $redis->blPop('key1', 10);
     * // blocking for 10 seconds
     *
     * // process 2
     * $redis->lPush('key1', 'A');
     *
     * // process 1
     * // array('key1', 'A') is returned
     * </pre>
     */
    public function blpop($keys, $timeout)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 移出并获取列表的最后一个元素， 如果列表没有元素会阻塞列表直到等待超时或发现可弹出元素为止。
     * Is a blocking rPop primitive. If at least one of the lists contains at least one element,
     * the element will be popped from the head of the list and returned to the caller.
     * Il all the list identified by the keys passed in arguments are empty, brPop will
     * block during the specified timeout until an element is pushed to one of those lists. T
     * his element will be popped.
     *
     * @param string|string[] $keys String array containing the keys of the lists OR variadic list of strings
     * @param int $timeout Timeout is always the required final parameter
     *
     * @return array ['listName', 'element']
     *
     * @link    https://redis.io/commands/brpop
     * @example
     * <pre>
     * // Non blocking feature
     * $redis->lPush('key1', 'A');
     * $redis->del('key2');
     *
     * $redis->blPop('key1', 'key2', 10); // array('key1', 'A')
     * // OR
     * $redis->blPop(array('key1', 'key2'), 10); // array('key1', 'A')
     *
     * $redis->brPop('key1', 'key2', 10); // array('key1', 'A')
     * // OR
     * $redis->brPop(array('key1', 'key2'), 10); // array('key1', 'A')
     *
     * // Blocking feature
     *
     * // process 1
     * $redis->del('key1');
     * $redis->blPop('key1', 10);
     * // blocking for 10 seconds
     *
     * // process 2
     * $redis->lPush('key1', 'A');
     *
     * // process 1
     * // array('key1', 'A') is returned
     * </pre>
     */
    public function brpop($keys, $timeout)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 从列表中弹出一个值，将弹出的元素插入到另外一个列表中并返回它； 如果列表没有元素会阻塞列表直到等待超时或发现可弹出元素为止。
     * A blocking version of rpoplpush, with an integral timeout in the third parameter.
     *
     * @param string $srcKey
     * @param string $dstKey
     * @param int $timeout
     *
     * @return  string|mixed|bool  The element that was moved in case of success, FALSE in case of timeout
     *
     * @link    https://redis.io/commands/brpoplpush
     */
    public function brpoplpush($source, $destination, $timeout)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 通过索引获取列表中的元素
     * Return the specified element of the list stored at the specified key.
     * 0 the first element, 1 the second ... -1 the last element, -2 the penultimate ...
     * Return FALSE in case of a bad index or a key that doesn't point to a list.
     *
     * @param string $key
     * @param int $index
     *
     * @return mixed|bool the element at this index
     *
     * Bool FALSE if the key identifies a non-string data type, or no value corresponds to this index in the list Key.
     *
     * @link    https://redis.io/commands/lindex
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');  // key1 => [ 'A', 'B', 'C' ]
     * $redis->lIndex('key1', 0);     // 'A'
     * $redis->lIndex('key1', -1);    // 'C'
     * $redis->lIndex('key1', 10);    // `FALSE`
     * </pre>
     */
    public function lindex($key, $index)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 在列表的元素前或者后插入元素
     * Insert value in the list before or after the pivot value. the parameter options
     * specify the position of the insert (before or after). If the list didn't exists,
     * or the pivot didn't exists, the value is not inserted.
     *
     * @param string $key
     * @param int $position Redis::BEFORE | Redis::AFTER
     * @param string $pivot
     * @param string|mixed $value
     *
     * @return int The number of the elements in the list, -1 if the pivot didn't exists.
     *
     * @link    https://redis.io/commands/linsert
     * @example
     * <pre>
     * $redis->del('key1');
     * $redis->lInsert('key1', Redis::AFTER, 'A', 'X');     // 0
     *
     * $redis->lPush('key1', 'A');
     * $redis->lPush('key1', 'B');
     * $redis->lPush('key1', 'C');
     *
     * $redis->lInsert('key1', Redis::BEFORE, 'C', 'X');    // 4
     * $redis->lRange('key1', 0, -1);                       // array('A', 'B', 'X', 'C')
     *
     * $redis->lInsert('key1', Redis::AFTER, 'C', 'Y');     // 5
     * $redis->lRange('key1', 0, -1);                       // array('A', 'B', 'X', 'C', 'Y')
     *
     * $redis->lInsert('key1', Redis::AFTER, 'W', 'value'); // -1
     * </pre>
     */
    public function linsert($key, $whence, $pivot, $value)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 获取列表长度
     * Returns the size of a list identified by Key. If the list didn't exist or is empty,
     * the command returns 0. If the data type identified by Key is not a list, the command return FALSE.
     *
     * @param string $key
     *
     * @return int|bool The size of the list identified by Key exists.
     * bool FALSE if the data type identified by Key is not list
     *
     * @link    https://redis.io/commands/llen
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C'); // key1 => [ 'A', 'B', 'C' ]
     * $redis->lLen('key1');       // 3
     * $redis->rPop('key1');
     * $redis->lLen('key1');       // 2
     * </pre>
     */
    public function llen($key)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 移出并获取列表的第一个元素
     * Returns and removes the first element of the list.
     *
     * @param string $key
     *
     * @return  mixed|bool if command executed successfully BOOL FALSE in case of failure (empty list)
     *
     * @link    https://redis.io/commands/lpop
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');  // key1 => [ 'A', 'B', 'C' ]
     * $redis->lPop('key1');        // key1 => [ 'B', 'C' ]
     * </pre>
     */
    public function lpop($key)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 将一个或多个值插入到列表头部
     * Adds the string values to the head (left) of the list.
     * Creates the list if the key didn't exist.
     * If the key exists and is not a list, FALSE is returned.
     *
     * @param string $key
     * @param string|mixed $value1 ... Variadic list of values to push in key, if dont used serialized, used string
     *
     * @return int|bool The new length of the list in case of success, FALSE in case of Failure
     *
     * @link https://redis.io/commands/lpush
     * @example
     * <pre>
     * $redis->lPush('l', 'v1', 'v2', 'v3', 'v4')   // int(4)
     * var_dump( $redis->lRange('l', 0, -1) );
     * // Output:
     * // array(4) {
     * //   [0]=> string(2) "v4"
     * //   [1]=> string(2) "v3"
     * //   [2]=> string(2) "v2"
     * //   [3]=> string(2) "v1"
     * // }
     * </pre>
     */
    public function lpush($key, array $values)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 将一个值插入到已存在的列表头部
     * Adds the string value to the head (left) of the list if the list exists.
     *
     * @param string $key
     * @param string|mixed $value String, value to push in key
     *
     * @return int|bool The new length of the list in case of success, FALSE in case of Failure.
     *
     * @link    https://redis.io/commands/lpushx
     * @example
     * <pre>
     * $redis->del('key1');
     * $redis->lPushx('key1', 'A');     // returns 0
     * $redis->lPush('key1', 'A');      // returns 1
     * $redis->lPushx('key1', 'B');     // returns 2
     * $redis->lPushx('key1', 'C');     // returns 3
     * // key1 now points to the following list: [ 'A', 'B', 'C' ]
     * </pre>
     */
    public function lpushx($key, array $values)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 获取列表指定范围内的元素
     * Returns the specified elements of the list stored at the specified key in
     * the range [start, end]. start and stop are interpretated as indices: 0 the first element,
     * 1 the second ... -1 the last element, -2 the penultimate ...
     *
     * @param string $key
     * @param int $start
     * @param int $end
     *
     * @return array containing the values in specified range.
     *
     * @link    https://redis.io/commands/lrange
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');
     * $redis->lRange('key1', 0, -1); // array('A', 'B', 'C')
     * </pre>
     */
    public function lrange($key, $start, $stop)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 移除列表元素
     * Removes the first count occurrences of the value element from the list.
     * If count is zero, all the matching elements are removed. If count is negative,
     * elements are removed from tail to head.
     *
     * @param string $key
     * @param int $count
     * @param string $value
     *
     * @return int|bool the number of elements to remove
     * bool FALSE if the value identified by key is not a list.
     *
     * @link    https://redis.io/commands/lrem
     * @example
     * <pre>
     * $redis->lPush('key1', 'A');
     * $redis->lPush('key1', 'B');
     * $redis->lPush('key1', 'C');
     * $redis->lPush('key1', 'A');
     * $redis->lPush('key1', 'A');
     *
     * $redis->lRange('key1', 0, -1);   // array('A', 'A', 'C', 'B', 'A')
     * $redis->lRem('key1', 'A', 2);    // 2
     * $redis->lRange('key1', 0, -1);   // array('C', 'B', 'A')
     * </pre>
     */
    public function lrem($key, $count, $value)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 通过索引设置列表元素的值
     * Set the list at index with the new value.
     *
     * @param string $key
     * @param int $index
     * @param string $value
     *
     * @return bool TRUE if the new value is setted.
     * FALSE if the index is out of range, or data type identified by key is not a list.
     *
     * @link    https://redis.io/commands/lset
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');    // key1 => [ 'A', 'B', 'C' ]
     * $redis->lIndex('key1', 0);     // 'A'
     * $redis->lSet('key1', 0, 'X');
     * $redis->lIndex('key1', 0);     // 'X'
     * </pre>
     */
    public function lset($key, $index, $value)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 对一个列表进行修剪(trim)，就是说，让列表只保留指定区间内的元素，不在指定区间之内的元素都将被删除。
     * Trims an existing list so that it will contain only a specified range of elements.
     *
     * @param string $key
     * @param int $start
     * @param int $stop
     *
     * @return array|bool Bool return FALSE if the key identify a non-list value
     *
     * @link        https://redis.io/commands/ltrim
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');
     * $redis->lRange('key1', 0, -1); // array('A', 'B', 'C')
     * $redis->lTrim('key1', 0, 1);
     * $redis->lRange('key1', 0, -1); // array('A', 'B')
     * </pre>
     */
    public function ltrim($key, $start, $stop)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 移除列表的最后一个元素，返回值为移除的元素。
     * Returns and removes the last element of the list.
     *
     * @param string $key
     *
     * @return  mixed|bool if command executed successfully BOOL FALSE in case of failure (empty list)
     *
     * @link    https://redis.io/commands/rpop
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');  // key1 => [ 'A', 'B', 'C' ]
     * $redis->rPop('key1');        // key1 => [ 'A', 'B' ]
     * </pre>
     */
    public function rpop($key)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 移除列表的最后一个元素，并将该元素添加到另一个列表并返回
     * Pops a value from the tail of a list, and pushes it to the front of another list.
     * Also return this value.
     *
     * @param string $srcKey
     * @param string $dstKey
     *
     * @return string|mixed|bool The element that was moved in case of success, FALSE in case of failure.
     *
     * @since   redis >= 1.1
     *
     * @link    https://redis.io/commands/rpoplpush
     * @example
     * <pre>
     * $redis->del('x', 'y');
     *
     * $redis->lPush('x', 'abc');
     * $redis->lPush('x', 'def');
     * $redis->lPush('y', '123');
     * $redis->lPush('y', '456');
     *
     * // move the last of x to the front of y.
     * var_dump($redis->rpoplpush('x', 'y'));
     * var_dump($redis->lRange('x', 0, -1));
     * var_dump($redis->lRange('y', 0, -1));
     *
     * //Output:
     * //
     * //string(3) "abc"
     * //array(1) {
     * //  [0]=>
     * //  string(3) "def"
     * //}
     * //array(3) {
     * //  [0]=>
     * //  string(3) "abc"
     * //  [1]=>
     * //  string(3) "456"
     * //  [2]=>
     * //  string(3) "123"
     * //}
     * </pre>
     */
    public function rpoplpush($source, $destination)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 在列表末尾添加一个或多个值
     * Adds the string values to the tail (right) of the list.
     * Creates the list if the key didn't exist.
     * If the key exists and is not a list, FALSE is returned.
     *
     * @param string $key
     * @param string|mixed $value1 ... Variadic list of values to push in key, if dont used serialized, used string
     *
     * @return int|bool The new length of the list in case of success, FALSE in case of Failure
     *
     * @link    https://redis.io/commands/rpush
     * @example
     * <pre>
     * $redis->rPush('l', 'v1', 'v2', 'v3', 'v4');    // int(4)
     * var_dump( $redis->lRange('l', 0, -1) );
     * // Output:
     * // array(4) {
     * //   [0]=> string(2) "v1"
     * //   [1]=> string(2) "v2"
     * //   [2]=> string(2) "v3"
     * //   [3]=> string(2) "v4"
     * // }
     * </pre>
     */
    public function rpush($key, $values)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * 在已存在的列表末尾添加值
     * Adds the string value to the tail (right) of the list if the ist exists. FALSE in case of Failure.
     *
     * @param string $key
     * @param string|mixed $value String, value to push in key
     *
     * @return int|bool The new length of the list in case of success, FALSE in case of Failure.
     *
     * @link    https://redis.io/commands/rpushx
     * @example
     * <pre>
     * $redis->del('key1');
     * $redis->rPushx('key1', 'A'); // returns 0
     * $redis->rPush('key1', 'A'); // returns 1
     * $redis->rPushx('key1', 'B'); // returns 2
     * $redis->rPushx('key1', 'C'); // returns 3
     * // key1 now points to the following list: [ 'A', 'B', 'C' ]
     * </pre>
     */
    public function rpushx($key, $values)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * Adds a values to the set value stored at key.
     *
     * @param string $key Required key
     * @param string|mixed ...$value1 Variadic list of values
     *
     * @return int|bool The number of elements added to the set.
     * If this value is already in the set, FALSE is returned
     *
     * @link    https://redis.io/commands/sadd
     * @example
     * <pre>
     * $redis->sAdd('k', 'v1');                // int(1)
     * $redis->sAdd('k', 'v1', 'v2', 'v3');    // int(2)
     * </pre>
     */
    public function sadd($key, ...$members)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Adds a values to the set value stored at key.
     *
     * @param string $key Required key
     * @param array $values Required values
     * @param int $seconds The key's remaining Time To Live, in seconds
     *
     * @return  int|bool The number of elements added to the set.
     * If this value is already in the set, FALSE is returned
     *
     * @link    https://redis.io/commands/sadd
     * @link    https://github.com/phpredis/phpredis/commit/3491b188e0022f75b938738f7542603c7aae9077
     * @since   phpredis 2.2.8
     * @example
     * <pre>
     * $redis->sAddArray('k', array('v1'));                // boolean
     * $redis->sAddArray('k', array('v1', 'v2', 'v3'));    // boolean
     * </pre>
     */
    public function sAddArray($key, array $values, $seconds = null)
    {

        $parameters = func_get_args();
        unset($parameters[2]);
        $parameters = $this->handleParameters($parameters);
        if ($seconds === null) {
            return $this->__call(__FUNCTION__, $parameters);
        }

        $this->redis->multi();
        $this->__call(__FUNCTION__, $parameters);
        $this->pexpire($key, $seconds * 1000);
        $manyResult = $this->redis->exec();

        return [
            'result' => data_get($manyResult, 0),
            'pexpire' => data_get($manyResult, 1),
        ];

    }

    /**
     * Returns the cardinality of the set identified by key.
     *
     * @param string $key
     *
     * @return int the cardinality of the set identified by key, 0 if the set doesn't exist.
     *
     * @link    https://redis.io/commands/scard
     * @example
     * <pre>
     * $redis->sAdd('key1' , 'set1');
     * $redis->sAdd('key1' , 'set2');
     * $redis->sAdd('key1' , 'set3');   // 'key1' => {'set1', 'set2', 'set3'}
     * $redis->sCard('key1');           // 3
     * $redis->sCard('keyX');           // 0
     * </pre>
     */
    public function scard($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Performs the difference between N sets and returns it.
     *
     * @param string $key1 first key for diff
     * @param string ...$otherKeys variadic list of keys corresponding to sets in redis
     *
     * @return array string[] The difference of the first set will all the others
     *
     * @link    https://redis.io/commands/sdiff
     * @example
     * <pre>
     * $redis->del('s0', 's1', 's2');
     *
     * $redis->sAdd('s0', '1');
     * $redis->sAdd('s0', '2');
     * $redis->sAdd('s0', '3');
     * $redis->sAdd('s0', '4');
     *
     * $redis->sAdd('s1', '1');
     * $redis->sAdd('s2', '3');
     *
     * var_dump($redis->sDiff('s0', 's1', 's2'));
     *
     * //array(2) {
     * //  [0]=>
     * //  string(1) "4"
     * //  [1]=>
     * //  string(1) "2"
     * //}
     * </pre>
     */
    public function sdiff($key1, ...$otherKeys)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args(), 'all'));
    }

    /**
     * Performs the same action as sDiff, but stores the result in the first key
     *
     * @param string $dstKey the key to store the diff into.
     * @param string $key1 first key for diff
     * @param string ...$otherKeys variadic list of keys corresponding to sets in redis
     *
     * @return int|bool The cardinality of the resulting set, or FALSE in case of a missing key
     *
     * @link    https://redis.io/commands/sdiffstore
     * @example
     * <pre>
     * $redis->del('s0', 's1', 's2');
     *
     * $redis->sAdd('s0', '1');
     * $redis->sAdd('s0', '2');
     * $redis->sAdd('s0', '3');
     * $redis->sAdd('s0', '4');
     *
     * $redis->sAdd('s1', '1');
     * $redis->sAdd('s2', '3');
     *
     * var_dump($redis->sDiffStore('dst', 's0', 's1', 's2'));
     * var_dump($redis->sMembers('dst'));
     *
     * //int(2)
     * //array(2) {
     * //  [0]=>
     * //  string(1) "4"
     * //  [1]=>
     * //  string(1) "2"
     * //}
     * </pre>
     */
    public function sdiffstore($dstKey, $key1, ...$otherKeys)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args(), 'all'));
    }

    /**
     * Returns the members of a set resulting from the intersection of all the sets
     * held at the specified keys. If just a single key is specified, then this command
     * produces the members of this set. If one of the keys is missing, FALSE is returned.
     *
     * @param string $key1 keys identifying the different sets on which we will apply the intersection.
     * @param string ...$otherKeys variadic list of keys
     *
     * @return array contain the result of the intersection between those keys
     * If the intersection between the different sets is empty, the return value will be empty array.
     *
     * @link    https://redis.io/commands/sinter
     * @example
     * <pre>
     * $redis->sAdd('key1', 'val1');
     * $redis->sAdd('key1', 'val2');
     * $redis->sAdd('key1', 'val3');
     * $redis->sAdd('key1', 'val4');
     *
     * $redis->sAdd('key2', 'val3');
     * $redis->sAdd('key2', 'val4');
     *
     * $redis->sAdd('key3', 'val3');
     * $redis->sAdd('key3', 'val4');
     *
     * var_dump($redis->sInter('key1', 'key2', 'key3'));
     *
     * //array(2) {
     * //  [0]=>
     * //  string(4) "val4"
     * //  [1]=>
     * //  string(4) "val3"
     * //}
     * </pre>
     */
    public function sinter($key1, ...$otherKeys)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args(), 'all'));
    }

    /**
     * Performs a sInter command and stores the result in a new set.
     *
     * @param string $dstKey the key to store the diff into.
     * @param string $key1 keys identifying the different sets on which we will apply the intersection.
     * @param string ...$otherKeys variadic list of keys
     *
     * @return int|bool The cardinality of the resulting set, or FALSE in case of a missing key
     *
     * @link    https://redis.io/commands/sinterstore
     * @example
     * <pre>
     * $redis->sAdd('key1', 'val1');
     * $redis->sAdd('key1', 'val2');
     * $redis->sAdd('key1', 'val3');
     * $redis->sAdd('key1', 'val4');
     *
     * $redis->sAdd('key2', 'val3');
     * $redis->sAdd('key2', 'val4');
     *
     * $redis->sAdd('key3', 'val3');
     * $redis->sAdd('key3', 'val4');
     *
     * var_dump($redis->sInterStore('output', 'key1', 'key2', 'key3'));
     * var_dump($redis->sMembers('output'));
     *
     * //int(2)
     * //
     * //array(2) {
     * //  [0]=>
     * //  string(4) "val4"
     * //  [1]=>
     * //  string(4) "val3"
     * //}
     * </pre>
     */
    public function sinterstore($dstKey, $key1, ...$otherKeys)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args(), 'all'));
    }

    /**
     * Checks if value is a member of the set stored at the key key.
     *
     * @param string $key
     * @param string|mixed $value
     *
     * @return bool TRUE if value is a member of the set at key key, FALSE otherwise
     *
     * @link    https://redis.io/commands/sismember
     * @example
     * <pre>
     * $redis->sAdd('key1' , 'set1');
     * $redis->sAdd('key1' , 'set2');
     * $redis->sAdd('key1' , 'set3'); // 'key1' => {'set1', 'set2', 'set3'}
     *
     * $redis->sIsMember('key1', 'set1'); // TRUE
     * $redis->sIsMember('key1', 'setX'); // FALSE
     * </pre>
     */
    public function sismember($key, $member)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Returns the contents of a set.
     *
     * @param string $key
     *
     * @return array An array of elements, the contents of the set
     *
     * @link    https://redis.io/commands/smembers
     * @example
     * <pre>
     * $redis->del('s');
     * $redis->sAdd('s', 'a');
     * $redis->sAdd('s', 'b');
     * $redis->sAdd('s', 'a');
     * $redis->sAdd('s', 'c');
     * var_dump($redis->sMembers('s'));
     *
     * //array(3) {
     * //  [0]=>
     * //  string(1) "c"
     * //  [1]=>
     * //  string(1) "a"
     * //  [2]=>
     * //  string(1) "b"
     * //}
     * // The order is random and corresponds to redis' own internal representation of the set structure.
     * </pre>
     */
    public function smembers($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Moves the specified member from the set at srcKey to the set at dstKey.
     *
     * @param string $srcKey
     * @param string $dstKey
     * @param string|mixed $member
     *
     * @return bool If the operation is successful, return TRUE.
     * If the srcKey and/or dstKey didn't exist, and/or the member didn't exist in srcKey, FALSE is returned.
     *
     * @link    https://redis.io/commands/smove
     * @example
     * <pre>
     * $redis->sAdd('key1' , 'set11');
     * $redis->sAdd('key1' , 'set12');
     * $redis->sAdd('key1' , 'set13');          // 'key1' => {'set11', 'set12', 'set13'}
     * $redis->sAdd('key2' , 'set21');
     * $redis->sAdd('key2' , 'set22');          // 'key2' => {'set21', 'set22'}
     * $redis->sMove('key1', 'key2', 'set13');  // 'key1' =>  {'set11', 'set12'}
     *                                          // 'key2' =>  {'set21', 'set22', 'set13'}
     * </pre>
     */
    public function smove($source, $destination, $member)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args(), [0, 1]));
    }

    /**
     * Removes and returns a random element from the set value at Key.
     *
     * @param string $key
     * @param int $count [optional]
     *
     * @return string|mixed|array|bool "popped" values
     * bool FALSE if set identified by key is empty or doesn't exist.
     *
     * @link    https://redis.io/commands/spop
     * @example
     * <pre>
     * $redis->sAdd('key1' , 'set1');
     * $redis->sAdd('key1' , 'set2');
     * $redis->sAdd('key1' , 'set3');   // 'key1' => {'set3', 'set1', 'set2'}
     * $redis->sPop('key1');            // 'set1', 'key1' => {'set3', 'set2'}
     * $redis->sPop('key1');            // 'set3', 'key1' => {'set2'}
     *
     * // With count
     * $redis->sAdd('key2', 'set1', 'set2', 'set3');
     * var_dump( $redis->sPop('key2', 3) ); // Will return all members but in no particular order
     *
     * // array(3) {
     * //   [0]=> string(4) "set2"
     * //   [1]=> string(4) "set3"
     * //   [2]=> string(4) "set1"
     * // }
     * </pre>
     */
    public function spop($key, $count = null)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Returns a random element(s) from the set value at Key, without removing it.
     *
     * @param string $key
     * @param int $count [optional]
     *
     * @return string|mixed|array|bool value(s) from the set
     * bool FALSE if set identified by key is empty or doesn't exist and count argument isn't passed.
     *
     * @link    https://redis.io/commands/srandmember
     * @example
     * <pre>
     * $redis->sAdd('key1' , 'one');
     * $redis->sAdd('key1' , 'two');
     * $redis->sAdd('key1' , 'three');              // 'key1' => {'one', 'two', 'three'}
     *
     * var_dump( $redis->sRandMember('key1') );     // 'key1' => {'one', 'two', 'three'}
     *
     * // string(5) "three"
     *
     * var_dump( $redis->sRandMember('key1', 2) );  // 'key1' => {'one', 'two', 'three'}
     *
     * // array(2) {
     * //   [0]=> string(2) "one"
     * //   [1]=> string(5) "three"
     * // }
     * </pre>
     */
    public function srandmember($key, $count = null)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Removes the specified members from the set value stored at key.
     *
     * @param string $key
     * @param string|mixed ...$member1 Variadic list of members
     *
     * @return int The number of elements removed from the set
     *
     * @link    https://redis.io/commands/srem
     * @example
     * <pre>
     * var_dump( $redis->sAdd('k', 'v1', 'v2', 'v3') );    // int(3)
     * var_dump( $redis->sRem('k', 'v2', 'v3') );          // int(2)
     * var_dump( $redis->sMembers('k') );
     * //// Output:
     * // array(1) {
     * //   [0]=> string(2) "v1"
     * // }
     * </pre>
     */
    public function srem($key, $member)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Scan a set for members
     *
     * @param string $key The set to search.
     * @param int $iterator LONG (reference) to the iterator as we go.
     * @param string $pattern String, optional pattern to match against.
     * @param int $count How many members to return at a time (Redis might return a different amount)
     *
     * @return array|bool PHPRedis will return an array of keys or FALSE when we're done iterating
     *
     * @link    https://redis.io/commands/sscan
     * @example
     * <pre>
     * $iterator = null;
     * while ($members = $redis->sScan('set', $iterator)) {
     *     foreach ($members as $member) {
     *         echo $member . PHP_EOL;
     *     }
     * }
     * </pre>
     */
    public function sscan($key, &$iterator, $pattern = null, $count = 0)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Performs the union between N sets and returns it.
     *
     * @param string $key1 first key for union
     * @param string ...$otherKeys variadic list of keys corresponding to sets in redis
     *
     * @return array string[] The union of all these sets
     *
     * @link    https://redis.io/commands/sunionstore
     * @example
     * <pre>
     * $redis->sAdd('s0', '1');
     * $redis->sAdd('s0', '2');
     * $redis->sAdd('s1', '3');
     * $redis->sAdd('s1', '1');
     * $redis->sAdd('s2', '3');
     * $redis->sAdd('s2', '4');
     *
     * var_dump($redis->sUnion('s0', 's1', 's2'));
     *
     * array(4) {
     * //  [0]=>
     * //  string(1) "3"
     * //  [1]=>
     * //  string(1) "4"
     * //  [2]=>
     * //  string(1) "1"
     * //  [3]=>
     * //  string(1) "2"
     * //}
     * </pre>
     */
    public function sunion($key1, ...$otherKeys)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args(), 'all'));
    }

    /**
     * Performs the same action as sUnion, but stores the result in the first key
     *
     * @param string $dstKey the key to store the diff into.
     * @param string $key1 first key for union
     * @param string ...$otherKeys variadic list of keys corresponding to sets in redis
     *
     * @return int Any number of keys corresponding to sets in redis
     *
     * @link    https://redis.io/commands/sunionstore
     * @example
     * <pre>
     * $redis->del('s0', 's1', 's2');
     *
     * $redis->sAdd('s0', '1');
     * $redis->sAdd('s0', '2');
     * $redis->sAdd('s1', '3');
     * $redis->sAdd('s1', '1');
     * $redis->sAdd('s2', '3');
     * $redis->sAdd('s2', '4');
     *
     * var_dump($redis->sUnionStore('dst', 's0', 's1', 's2'));
     * var_dump($redis->sMembers('dst'));
     *
     * //int(4)
     * //array(4) {
     * //  [0]=>
     * //  string(1) "3"
     * //  [1]=>
     * //  string(1) "4"
     * //  [2]=>
     * //  string(1) "1"
     * //  [3]=>
     * //  string(1) "2"
     * //}
     * </pre>
     */
    public function sunionstore($dstKey, $key1, ...$otherKeys)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args(), 'all'));
    }

    /**
     * Adds the specified member with a given score to the sorted set stored at key
     *
     * @param string $key Required key
     * @param array $options Options if needed
     * @param float $score1 Required score
     * @param string|mixed $value1 Required value
     * @param float $score2 Optional score
     * @param string|mixed $value2 Optional value
     * @param float $scoreN Optional score
     * @param string|mixed $valueN Optional value
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
    public function zadd($key, $options, $score1, $value1, $score2 = null, $value2 = null, $scoreN = null, $valueN = null)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Returns the cardinality of an ordered set.
     *
     * @param string $key
     *
     * @return int the set's cardinality
     *
     * @link    https://redis.io/commands/zsize
     * @example
     * <pre>
     * $redis->zAdd('key', 0, 'val0');
     * $redis->zAdd('key', 2, 'val2');
     * $redis->zAdd('key', 10, 'val10');
     * $redis->zCard('key');            // 3
     * </pre>
     */
    public function zcard($key)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Returns the number of elements of the sorted set stored at the specified key which have
     * scores in the range [start,end]. Adding a parenthesis before start or end excludes it
     * from the range. +inf and -inf are also valid limits.
     *
     * @param string $key
     * @param string $start
     * @param string $end
     *
     * @return int the size of a corresponding zRangeByScore
     *
     * @link    https://redis.io/commands/zcount
     * @example
     * <pre>
     * $redis->zAdd('key', 0, 'val0');
     * $redis->zAdd('key', 2, 'val2');
     * $redis->zAdd('key', 10, 'val10');
     * $redis->zCount('key', 0, 3); // 2, corresponding to array('val0', 'val2')
     * </pre>
     */
    public function zcount($key, $start, $end)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    /**
     * Increments the score of a member from a sorted set by a given amount.
     *
     * @param string $key
     * @param float $value (double) value that will be added to the member's score
     * @param string $member
     *
     * @return float the new value
     *
     * @link    https://redis.io/commands/zincrby
     * @example
     * <pre>
     * $redis->del('key');
     * $redis->zIncrBy('key', 2.5, 'member1');  // key or member1 didn't exist, so member1's score is to 0
     *                                          // before the increment and now has the value 2.5
     * $redis->zIncrBy('key', 1, 'member1');    // 3.5
     * </pre>
     */
    public function zincrby($key, $value, $member)
    {
        return $this->__call(__FUNCTION__, $this->handleParameters(func_get_args()));
    }

    public function zinterstore($destination, $keys, array $options = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zrange($key, $start, $stop, array $options = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zrangebyscore($key, $min, $max, array $options = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zrank($key, $member)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zrem($key, $member)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zremrangebyrank($key, $start, $stop)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zremrangebyscore($key, $min, $max)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zrevrange($key, $start, $stop, array $options = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zrevrangebyscore($key, $max, $min, array $options = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zrevrank($key, $member)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zunionstore($destination, $keys, array $options = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zscore($key, $member)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zscan($key, $cursor, array $options = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zrangebylex($key, $start, $stop, array $options = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zrevrangebylex($key, $start, $stop, array $options = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zremrangebylex($key, $min, $max)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function zlexcount($key, $min, $max)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function pfadd($key, array $elements)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function pfmerge($destinationKey, $sourceKeys)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function pfcount($keys)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function pubsub($subcommand, $argument)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function publish($channel, $message)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function discard()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return void|array
     *
     * @see multi()
     * @link https://redis.io/commands/exec
     */
    public function exec()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * Enter and exit transactional mode.
     *
     * @param int $mode Redis::MULTI|Redis::PIPELINE
     * Defaults to Redis::MULTI.
     * A Redis::MULTI block of commands runs as a single transaction;
     * a Redis::PIPELINE block is simply transmitted faster to the server, but without any guarantee of atomicity.
     * discard cancels a transaction.
     *
     * @return Redis returns the Redis instance and enters multi-mode.
     * Once in multi-mode, all subsequent method calls return the same object until exec() is called.
     *
     * @link    https://redis.io/commands/multi
     * @example
     * <pre>
     * $ret = $redis->multi()
     *      ->set('key1', 'val1')
     *      ->get('key1')
     *      ->set('key2', 'val2')
     *      ->get('key2')
     *      ->exec();
     *
     * //$ret == array (
     * //    0 => TRUE,
     * //    1 => 'val1',
     * //    2 => TRUE,
     * //    3 => 'val2');
     * </pre>
     */
    public function multi()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function unwatch()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function watch($key)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function eval($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function evalsha($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function script($subcommand, $argument = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function auth($password)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function echo($message)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function ping($message = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function select($database)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function bgrewriteaof()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function bgsave()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function client($subcommand, $argument = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function config($subcommand, $argument = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function dbsize()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function flushall()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function flushdb()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function info($section = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function lastsave()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function save()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function slaveof($host, $port)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function slowlog($subcommand, $argument = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function time()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function command()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function geoadd($key, $longitude, $latitude, $member)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function geohash($key, array $members)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function geopos($key, array $members)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function geodist($key, $member1, $member2, $unit = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function georadius($key, $longitude, $latitude, $radius, $unit, array $options = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function georadiusbymember($key, $member, $radius, $unit, array $options = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}
