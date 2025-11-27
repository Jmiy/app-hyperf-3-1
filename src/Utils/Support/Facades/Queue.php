<?php

namespace Business\Hyperf\Utils\Support\Facades;

use function Hyperf\Support\make;
use Hyperf\Context\ApplicationContext;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;

class Queue
{

    /**
     * public static function connection($queue = null): DriverInterface
     * @param mixed|null $queue 消息队列配置名称 默认：null(使用默认消息队列：default)
     * @return DriverInterface
     */
    public static function connection(mixed $queue = null): DriverInterface
    {
        return ApplicationContext::getContainer()->get(DriverFactory::class)->get($queue ?? 'default');
    }

    /**
     * 生产消息 public static function push($job, $data = null, int $delay = 0, $connection = null, $channel = null): bool
     * @param mixed $job job对象|类
     * @param mixed|null $data job类 参数
     * @param int $delay 延时执行时间 (单位：秒)
     * @param mixed|null $connection 消息队列配置名称 默认：null(使用默认消息队列：default)
     * @param mixed|null $channel 队列名 默认取 $connection 对应的配置的 channel 队列名 暂时不支持动态修改
     * @return bool
     */
    public static function push(mixed $job, mixed $data = null, int $delay = 0, mixed $connection = null, mixed $channel = null): bool
    {
        if (!is_object($job)) {
            $job = make($job, $data);
        }
        return static::connection($connection)->push($job, $delay);
    }
}
