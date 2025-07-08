<?php

namespace Business\Hyperf\Utils\Support\Facades;

use function Hyperf\Support\call;

class BloomFilter
{
    public static $hashFunctions = ['hash1', 'hash2', 'hash'];

    public static function setHashFunctions($hashFunctions = ['hash1', 'hash2', 'hash'])
    {
        static::$hashFunctions = $hashFunctions;
    }

    /**
     * 哈希函数1
     * @param string $string
     * @param int $size 布隆过滤器空间大小
     * @return int
     */
    public static function hash1(string $string, int $size)
    {
        $hash = 0;
        $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash = ($hash * 31 + ord($string[$i])) % $size;
        }

        return $hash;
    }

    /**
     * 哈希函数2
     * @param string $string
     * @param int $size 布隆过滤器空间大小
     * @return int
     */
    public static function hash2(string $string, int $size)
    {
        $hash = 5381;
        $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash = ($hash << 5) + $hash + ord($string[$i]);
        }
        return abs($hash % $size);
    }

    /**
     * 哈希函数
     * @param string $string
     * @param int|null $size 布隆过滤器空间大小
     * @return float|int
     */
    public static function hash(string $string, int $size = null)
    {
        if ($size === null) {
            return crc32($string);
        }

        return abs(crc32($string) % $size);
    }

    /**
     * 添加元素到布隆过滤器
     * @param string $item 要添加的元素
     */
    public static function add(string $key, string $item, ?int $size = 10000, int $seconds = null, string $poolName = 'default')
    {
        foreach (static::$hashFunctions as $hashFn) {

            if (method_exists(static::class, $hashFn)) {
                $hashFn = [static::class, $hashFn];
            }
            $hash = call($hashFn, [$item, $size]);

            Redis::setBit($key, $hash, true, $seconds, $poolName);
        }
    }

    /**
     * 检查元素是否可能在布隆过滤器中
     * @param string $item 要检查的元素
     * @return bool 如果可能存在返回true，否则返回false
     */
    public function exists(string $key, string $item, ?int $size = 10000, ?string $poolName = 'default')
    {
        $redis = Redis::getRedis($poolName);
        foreach (static::$hashFunctions as $hashFn) {

            if (method_exists(static::class, $hashFn)) {
                $hashFn = [static::class, $hashFn];
            }
            $hash = call($hashFn, [$item, $size]);

            if ($redis->getBit($key, $hash) == 0) {
                return false;
            }
        }

        return true;
    }

}
