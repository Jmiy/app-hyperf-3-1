<?php

/**
 * base trait
 * User: Jmiy
 * Date: 2019-05-16
 * Time: 16:50
 */

namespace Business\Hyperf\Service\Traits;

use function Hyperf\Support\call;
use function Hyperf\Config\config;
use function Business\Hyperf\Utils\Collection\data_get;
use Hyperf\Collection\Arr;
use function Hyperf\Tappable\tap;
use Business\Hyperf\Utils\Support\Facades\Cache;
use Business\Hyperf\Utils\Support\Facades\Redis;
use Business\Hyperf\Constants\Constant;
use Hyperf\Cache\CacheManager;
use Hyperf\Cache\Listener\DeleteListenerEvent;
use Hyperf\Cache\Annotation\Cacheable;
use Psr\EventDispatcher\EventDispatcherInterface;
use Hyperf\Cache\Driver\RedisDriver;

trait HandleCache
{
    /**
     * 获取缓存时间 单位秒
     * @param int|null $ttl 单位秒 或者 null
     * @param string $key 缓存时间在配置中的key
     * @param string $group 缓存配置 group
     * @return int|mixed
     */
    public static function getCacheTtl(int $ttl = null, string $key = 'ttl', string $group = 'default')
    {
        return $ttl !== null ? $ttl : config('cache.' . $group . '.' . $key, 86400); //认证缓存时间 单位秒
    }

    /**
     * 获取缓存前缀
     * @return string
     */
    public static function getCachePrefix()
    {
        return strtolower(static::getCustomClassName());
    }

    /**
     * 获取缓存key
     * @param ...$keys
     * @return string
     */
    public static function getCacheKey(...$keys)
    {
        return strtolower(implode(':', Arr::collapse([
                [static::getCachePrefix()],
                func_get_args()
            ]
        )));
    }

    /**
     * 获取集合成员
     * @param array $member 成员
     * @return string $member
     */
    public static function getZsetMember($member)
    {
        return json_encode($member, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取集合成员原始数据
     * @param string $member 成员 json
     * @return array $member
     */
    public static function getSrcMember($member)
    {
        return json_decode($member, true);
    }

    /********************Laravel 缓存系统 统一入口 start **********************************************/
    /**
     * 获取要清空的tags
     * @return array
     */
    public static function getClearTags()
    {
        return [static::getCacheTags()];
    }

    public static function getCacheTags()
    {
        return 'cacheTags';
    }

    /**
     * Laravel 缓存系统 统一入口
     * @param string $tag
     * @param array $actionData
     * @return \Hyperf\Utils\HigherOrderTapProxy|mixed|null
     */
    public static function handleCache($tag = '', $actionData = [])
    {
        $tags = config('cache.tags.' . $tag, ['{' . $tag . '}']);
        //$service = data_get($actionData, Constant::SERVICE, '');
        $service = Cache::class;
        $method = data_get($actionData, Constant::METHOD, '');
        $parameters = data_get($actionData, Constant::PARAMETERS, []);

        if ($tag) {
            $instance = call([call([$service, 'tags'], [$tags]), $method], $parameters);
        } else {
            $instance = call([$service, $method], $parameters);
        }

        $serialHandle = data_get($actionData, Constant::SERIAL_HANDLE, []);
        if ($serialHandle) {
//            foreach ($serialHandle as $handleData) {
//                $service = data_get($handleData, 'service', '');
//                $method = data_get($handleData, 'method', '');
//                $parameters = data_get($handleData, 'parameters', []);
//                $instance = $instance->{$method}(...$parameters);
//            }

            foreach ($serialHandle as $handleData) {
                $instance = tap($instance, function (&$instance) use ($handleData) {
                    $service = data_get($handleData, Constant::SERVICE, '');
                    $method = data_get($handleData, Constant::METHOD, '');
                    $parameters = data_get($handleData, Constant::PARAMETERS, []);
                    //$instance = $instance->{$method}(...$parameters);
                    $instance = call([$instance, $method], $parameters);
                });
            }
        }

        return $instance;
    }

    /**
     * 清空缓存
     */
    public static function clear()
    {

        $tags = static::getClearTags();
        $rs = false;
        $service = static::getNamespaceClass();
        $method = 'flush';
        $parameters = [];
        foreach ($tags as $tag) {
            $rs = static::handleCache($tag, getJobData($service, $method, $parameters, []));
        }

        return $rs;
    }

    /**
     * 释放分布式锁
     * @param string $cacheKey key
     * @param string $method 方法
     * @param int $releaseTime 释放边界值 单位秒
     * @return void
     */
    public static function forceReleaseLock($cacheKey, $method = 'forceRelease', $releaseTime = 10)
    {

//        loger('sys', 'sys')->info(
//            sprintf(
//                '[' .  __METHOD__ . '] [cacheKey: %s] [method: %s] [releaseTime: %d].',
//                '释放分布式锁: '.$cacheKey,
//                $method,
//                $releaseTime
//            )
//        );

        $service = static::getNamespaceClass();
        $tag = static::getCacheTags();

        if ($releaseTime == 0) {
            //释放锁
            $handleCacheData = getJobData($service, 'lock', [$cacheKey], null, [
                Constant::SERIAL_HANDLE => [
                    getJobData($service, $method, []),
                ]
            ]);
            return static::handleCache($tag, $handleCacheData);
        }

        $key = $cacheKey . ':statisticsLock';
        $handleCacheData = getJobData($service, 'has', [$key]);
        $has = static::handleCache($tag, $handleCacheData);

        switch ($method) {
            case 'forceRelease'://释放锁
                if ($has) {
                    $handleCacheData = getJobData($service, 'get', [$key]);
                    $releaseLockTime = static::handleCache($tag, $handleCacheData);
                    $nowTime = time();
                    if ($nowTime >= $releaseLockTime) {

                        //删除统计
                        $handleCacheData = getJobData($service, 'forget', [$key]);
                        static::handleCache($tag, $handleCacheData);

                        //释放锁
                        $handleCacheData = getJobData($service, 'lock', [$cacheKey], null, [
                            Constant::SERIAL_HANDLE => [
                                getJobData($service, $method, []),
                            ]
                        ]);
                        static::handleCache($tag, $handleCacheData);
                    }
                }

                break;

            case 'statisticsLock'://统计锁
                //increment('key', $amount)
                if (empty($has)) {
                    $time = time() + $releaseTime;
                    $handleCacheData = getJobData($service, 'add', [$key, $time]); //, 600
                    static::handleCache($tag, $handleCacheData);
                }
                break;

            default:
                break;
        }

        return true;
    }

    /**
     * 使用分布式锁处理
     * @param array $cacheKeyData key
     * @param array $parameters 分布式锁参数
     * @return mixed
     */
    public static function handleLock($cacheKeyData, $parameters = [])
    {
        $tag = static::getCacheTags();
        $cacheKey = implode(':', $cacheKeyData);
        $service = static::getNamespaceClass();
        $handleCacheData = getJobData($service, 'lock', [$cacheKey], null, [
            Constant::SERIAL_HANDLE => [
                getJobData($service, 'get', $parameters),
            ]
        ]);

        return static::handleCache($tag, $handleCacheData);
    }
    /********************Laravel 缓存系统 统一入口 end **********************************************/

    /**
     * 删除缓存数据
     * @param string|array $key
     */
    public static function del($key)
    {
        return Redis::del($key);
    }

    /**
     * 获取缓存Driver
     * @param string $group
     * @return \Hyperf\Cache\Driver\RedisDriver
     */
    public static function getCacheDriver(string $group = 'default'): RedisDriver
    {//* @return \Hyperf\Cache\Driver\DriverInterface
        return getApplicationContainer()->get(CacheManager::class)->getDriver($group);
    }

//    /**
//     * 注解方式 @Cacheable 生成的缓存,只能作用于 非trait 类方法
//     * @param $id
//     * @return string
//     */
//    #[Cacheable(prefix: "cacheable_demo", ttl: 9000, value: "_#{id}", listener: "user-update")]
//    public static function cacheableDemo($id)
//    {
//        var_dump(__METHOD__);
//        return $id . '_' . uniqid();
//    }

    //清理 @Cacheable 生成的缓存
    public static function flushCache(string $listener, array $arguments)
    {
        return getApplicationContainer()->get(EventDispatcherInterface::class)->dispatch(new DeleteListenerEvent($listener, $arguments));
    }

    public static function clearCachePrefix(string $prefix, string $group = 'default'): bool
    {
        return static::getCacheDriver($group)->clearPrefix($prefix);
    }

    /**
     * 清空功能权限缓存
     * @return array
     */
    public static function clearCacheModelPrefix(string $prefix = '', string $group = 'default')
    {
        //var_dump(implode(':',[static::class,__FUNCTION__,$prefix, $group]));
        return static::clearCachePrefix(static::getCacheKey($prefix), $group);//
    }

}
