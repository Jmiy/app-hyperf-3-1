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
     * 布隆过滤器参数计算函数
     * @param int $n 预期元素数量
     * @param float $p 可接受的误判率（0 < p < 1）
     * @return array
     */
    public static function calculateBloomFilterParameters(int $n, float $p)
    {
        // 计算位数组大小 m = - (n * ln(p)) / (ln(2)^2)
        // log(float $num, float $base = M_E)以 base 为底 num 的对数，如果未指定 num 则为自然对数。 例如：log(100,10)=2 log(2,2)=1  ln(p)=log(p)
        // pow(mixed $num, mixed $exponent) num 的 exponent 次方。如果两个参数都是非负整数且结果可以用整数表示，则返回 int 类型，否则返回 float。
        $m = -($n * log($p)) / pow(log(2), 2);
        $m = ceil($m); // 向上取整

        // 计算最优哈希函数数量 k = (m / n) * ln(2)
        $k = ($m / $n) * log(2);
        $k = ceil($k); // 向上取整

        // 计算实际误判率 exp(float $num):返回 e 的 num 次方值,用“e”作为自然对数的底数，大约为 2.718282。
        $actual_p = pow(1 - exp(-$k * $n / $m), $k);

        return [
            'm' => (int)$m,//计算的位数组大小
            'k' => (int)$k,//最优哈希函数数量
            'actual_p' => $actual_p,//实际误判率
            'bits_per_item' => $m / $n,//每元素占用比特数
            'n' => $n,//预期元素数量
            'p' => $p,//可接受误判率
        ];
    }

    /**
     * 生成k个哈希函数
     * @param int $k 哈希函数数量
     * @return array 哈希函数数组
     */
    public static function generateHashFunctions(int $k)
    {
        $hashFunctions = [];

        // 基础哈希函数1：FNV-1a (32位)
        $fnv1a = function ($item) {
            $hash = 2166136261; // FNV偏移基础值
            $len = strlen($item);
            for ($i = 0; $i < $len; $i++) {
                $hash ^= ord($item[$i]);
                $hash = (int)($hash * 16777619);
            }
            return $hash;
        };

        // 基础哈希函数2：MurmurHash变体
        $murmur = function ($item) {
            $seed = 0x3f6a2b4c; // 随机种子
            $len = strlen($item);
            $hash = $seed ^ $len;

            for ($i = 0; $i < $len; $i++) {
                $hash ^= ord($item[$i]) << (($i % 4) * 8);
                $hash = (int)($hash * 0x5bd1e995);
                $hash ^= $hash >> 15;
            }

            return $hash;
        };

        // 生成k个哈希函数
        for ($i = 0; $i < $k; $i++) {
            $hashFunctions[] = function ($item) use ($fnv1a, $murmur, $i) {
                // 使用两个基础哈希函数组合生成多个哈希函数
                $h1 = $fnv1a($item . $i);
                $h2 = $murmur($item . ($i * 0x9e3779b9));
                return abs($h1 ^ $h2);
            };
        }

        return $hashFunctions;
    }

    /**
     * 添加元素到布隆过滤器
     * @param string $item 要添加的元素
     */
    public static function add(string $key, string $item, int $k, ?int $size = 10000, int $seconds = null, string $poolName = 'default')
    {
        $hashFunctions = static::generateHashFunctions($k);
        foreach ($hashFunctions as $hashFn) {
            $hash = call($hashFn, [$item]);
            $hash = $hash % $size;

            Redis::setBit($key, $hash, true, $seconds, $poolName);
        }
    }

    /**
     * 检查元素是否可能在布隆过滤器中
     * @param string $item 要检查的元素
     * @return bool 如果可能存在返回true，否则返回false
     */
    public static function exists(string $key, string $item, int $k, ?int $size = 10000, ?string $poolName = 'default')
    {
        $redis = Redis::getRedis($poolName);
        $hashFunctions = static::generateHashFunctions($k);
        foreach ($hashFunctions as $hashFn) {
            $hash = call($hashFn, [$item]);
            $hash = $hash % $size;

            if ($redis->getBit($key, $hash) == 0) {
                return false;
            }
        }

        return true;
    }


}
