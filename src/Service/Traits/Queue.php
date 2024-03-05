<?php

/**
 * base trait
 * User: Jmiy
 * Date: 2019-05-16
 * Time: 16:50
 */

namespace Business\Hyperf\Service\Traits;

use function Business\Hyperf\Utils\Collection\data_get;
use function Hyperf\Config\config;
use Hyperf\Collection\Arr;
use Hyperf\Coroutine\Coroutine;
use Business\Hyperf\Constants\Constant;

trait Queue
{
    /**
     * 生产消息
     * @param callable $callback 回调闭包|回调类
     * @param string|null $method 类方法
     * @param array|null $parameters 方法参数
     * @param int|null $delay 延迟时间 默认：0
     * @param string|null $queueConnection 消息队列
     * @param array|int[]|null $extData 扩展参数 有用控制重试次数 睡眠时间等
     * @param array|null $request 请求
     * @return bool
     * @throws \Throwable
     */
    public static function push(
        $callback,
        ?string $method = '',
        ?array $parameters = [],
        ?int $delay = 0,
        ?string $queueConnection = Constant::QUEUE_CONNECTION_DEFAULT,
        ?array $extData = [
            Constant::RETRY_MAX => 3,
            Constant::SLEEP_MIN => 0,
            Constant::SLEEP_MAX => 1,
        ],
        ?array $request = null
    ): bool
    {
        $extData = Arr::collapse([
            [
                Constant::QUEUE_CONNECTION => $queueConnection,
                Constant::QUEUE_DELAY => $delay === null ? rand(1, 10) : $delay,
            ],
            $extData
        ]);

        $job = getJobData($callback, $method, $parameters, $request, $extData);

        return pushQueue($job);

//        $isPush = true;
//        $retryMax = data_get($extData, Constant::RETRY_MAX, 3);
//        $sleepMin = data_get($extData, Constant::SLEEP_MIN, 0);
//        $sleepMax = data_get($extData, Constant::SLEEP_MAX, 1);
//        for ($i = 0; $i < $retryMax; $i++) {
//            $isPush = pushQueue($job);
//            if ($isPush) {
//                break;
//            }
//            //如果压入队列失败，就睡眠 $sleepMin-$sleepMax，等待redis恢复
//            Coroutine::sleep(rand($sleepMin, $sleepMax));
//        }
//
//        return $isPush;
    }

    /**
     * 获取队列基本数据
     * @return string
     */
    public static function getQueueData($queue = Constant::QUEUE_EBAY)
    {
        $poolName = config('async_queue.' . $queue . '.redis.pool');

        return [
            Constant::QUEUE => $queue,
            Constant::POOL_NAME => $poolName,
        ];
    }

}
