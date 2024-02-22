<?php

declare(strict_types=1);

use Hyperf\AsyncQueue\Driver\RedisDriver;
use function Hyperf\Support\env;
/**
 * This file is part of Hyperf.(用于管理基于 Redis 实现的简易队列服务)
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    env('DEFAULT_QUEUE', 'default') => [
        'driver' => RedisDriver::class,
        'redis' => [
            'pool' => env('DEFAULT_QUEUE_DRIVER_REDIS_POOL', 'default'),//redis 连接池
        ],
        'channel' => env('DEFAULT_QUEUE_CHANNEL', '{queue}'),//队列前缀
        'timeout' => 2,//brPop 移出并获取列表的最后一个元素， 如果列表没有元素会阻塞列表直到等待超时或发现可弹出元素为止。   如果列表没有元素会阻塞列表直到等待超时：等待超时时间 5秒
        'retry_seconds' => [5, 10, 20],//失败后重新尝试间隔
        'handle_timeout' => 6000,//消息处理超时时间
        'processes' => 1,//消费进程数
        'concurrent' => [
            'limit' => 10,//同时处理消息数
        ],
    ],
];
