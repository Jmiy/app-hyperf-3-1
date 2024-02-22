<?php

namespace Business\Hyperf\Utils\Support\Facades;

use Hyperf\Collection\Arr;
use Hyperf\Coroutine\Concurrent;
use Business\Hyperf\Constants\Constant;
use Hyperf\AsyncQueue\Driver\ChannelConfig;

class QueueRedisDriver
{

    public static function getKey(string|array $connection, string|array $table, array $lockKeys = [])
    {
        return strtolower(implode(':', array_filter(
                    Arr::collapse(
                        [
                            is_array($connection) ? $connection : [$connection],
                            is_array($table) ? $table : [$table],
                            $lockKeys
                        ]
                    )
                )
            )
        );
    }

    public static function getChannelConfig(string $channel)
    {
        return make(ChannelConfig::class, ['channel' => $channel]);
    }


    public static function push(?string $poolName = 'default', ?string $channel = '', $data = null, int $delay = 0): bool
    {
        $channel = static::getChannelConfig($channel);
        $redis = Redis::getRedis($poolName);

        if ($delay === 0) {
            return (bool)$redis->lPush($channel->getWaiting(), $data);
        }

        return $redis->zAdd($channel->getDelayed(), time() + $delay, $data) > 0;
    }

    public static function pop(?string $poolName = 'default', ?string $channel = '', $limit = 50, $handleTimeout = 10): mixed
    {
        $channel = static::getChannelConfig($channel);
        $redis = Redis::getRedis($poolName);

        //将延迟队列中到期的消息压入正在执行队列
        static::move($poolName, $channel->getDelayed(), $channel->getWaiting());

        //将执行超时的消息压入超时队列
        static::move($poolName, $channel->getReserved(), $channel->getTimeout());

        //弹出待执行的消息
        $data = [];
        for ($i = 0; $i < $limit; $i++) {

            $res = $redis->brPop($channel->getWaiting(), 2);
            if (!isset($res[1])) {//如果待执行队列没有数据了，就跳出整个循环
                break;
            }

            $item = $res[1];
            $data[] = $item;

            //将待执行的消息压入正在执行队列
            $redis->zadd($channel->getReserved(), time() + $handleTimeout, $item);
        }

        return $data;
    }

    public static function consume(?string $poolName = 'default', ?string $channel = '', int $limit = 50, $handleTimeout = 10, $callBack = null): mixed
    {
        $data = static::pop($poolName, $channel, $limit, $handleTimeout);

        if (!empty($data)) {
            $callback = static::getCallback($poolName, $channel, $data, $callBack);

            $concurrent = new Concurrent(10);
            $concurrent->create($callback);
        }

        //将超时队列消息重新入到待执行队列
        static::reload($poolName, $channel, 'timeout');

        return $data;
    }

    public static function checkQueueLength(): void
    {
        $info = static::info();
    }

    public static function getCallback(?string $poolName = 'default', ?string $channel = '', $data = [], $callBack = null): callable
    {
        return function () use ($poolName, $channel, $data, $callBack) {

            $handleCallBack = [
                'ack' => getJobData(static::class, 'ack', [
                        $poolName, $channel, $data
                    ]
                ),

                'fail' => getJobData(static::class, 'fail', [
                        $poolName, $channel, $data
                    ]
                ),
            ];

            $service = data_get($callBack, Constant::SERVICE, '');
            $method = data_get($callBack, Constant::METHOD, '');
            $parameters = data_get($callBack, Constant::PARAMETERS, []);
            $parameters[] = $data;
            $parameters[] = $handleCallBack;

            call([$service, $method], $parameters);//兼容各种调用 $service::{$method}(...$parameters);
        };
    }

    /**
     * Remove data from delayed queue.
     */
    public static function delete(?string $poolName = 'default', ?string $channel = '', $data = []): bool
    {
        $redis = Redis::getRedis($poolName);
        $channel = static::getChannelConfig($channel);
        return (bool)$redis->zRem($channel->getDelayed(), $data);
    }

    /**
     * Remove data from reserved queue.
     */
    public static function ack(?string $poolName = 'default', ?string $channel = '', mixed $data = []): bool
    {
        return static::remove($poolName, $channel, $data);
    }

    /**
     * Remove data from reserved queue.
     */
    public static function remove(?string $poolName = 'default', ?string $channel = '', mixed $data = []): bool
    {
        $redis = Redis::getRedis($poolName);
        $channel = static::getChannelConfig($channel);
        return $redis->zrem($channel->getReserved(), ...$data) > 0;
    }

    /**
     * Remove data from reserved queue.
     * lPush data to failed queue.
     */
    public static function fail(?string $poolName = 'default', ?string $channel = '', mixed $data = []): bool
    {
        $redis = Redis::getRedis($poolName);
        $channel = static::getChannelConfig($channel);
        if (static::remove($poolName, $channel, $data)) {//Remove data from reserved queue.
            foreach ($data as $item) {
                $redis->lPush($channel->getFailed(), $item);
            }
            return true;
        }

        return false;
    }

    public static function reload(?string $poolName = 'default', ?string $channel = '', string $queue = null): int
    {
        $redis = Redis::getRedis($poolName);
        $_channel = static::getChannelConfig($channel);

        $channel = $_channel->getFailed();
        if ($queue) {
            if (!in_array($queue, ['timeout', 'failed'])) {
                throw new InvalidQueueException(sprintf('Queue %s is not supported.', $queue));
            }

            $channel = $_channel->get($queue);
        }

        $num = 0;
        while ($redis->rpoplpush($channel, $_channel->getWaiting())) {
            ++$num;
        }
        return $num;
    }

    public static function flush(?string $poolName = 'default', ?string $channel = '', string $queue = null): bool
    {
        $redis = Redis::getRedis($poolName);
        $_channel = static::getChannelConfig($channel);

        $channel = $_channel->getFailed();
        if ($queue) {
            $channel = $_channel->get($queue);
        }

        return (bool)$redis->del($channel);
    }

    public static function info(?string $poolName = 'default', ?string $channel = ''): array
    {
        $redis = Redis::getRedis($poolName);
        $channel = static::getChannelConfig($channel);
        return [
            'waiting' => $redis->lLen($channel->getWaiting()),
            'delayed' => $redis->zCard($channel->getDelayed()),
            'failed' => $redis->lLen($channel->getFailed()),
            'timeout' => $redis->lLen($channel->getTimeout()),
            'reserved' => $redis->zCard($channel->getReserved()),
        ];
    }

    protected function retry(MessageInterface $message): bool
    {
        $data = $this->packer->pack($message);

        $delay = time() + $this->getRetrySeconds($message->getAttempts());

        return $this->redis->zAdd($this->channel->getDelayed(), $delay, $data) > 0;
    }

    protected function getRetrySeconds(int $attempts): int
    {
        if (!is_array($this->retrySeconds)) {
            return $this->retrySeconds;
        }

        if (empty($this->retrySeconds)) {
            return 10;
        }

        return $this->retrySeconds[$attempts - 1] ?? end($this->retrySeconds);
    }


    /**
     * Move message to the waiting queue.
     */
    public static function move(?string $poolName = 'default', ?string $from = '', ?string $to = ''): void
    {
        $now = time();
        $options = ['LIMIT' => [0, 100]];
        $redis = Redis::getRedis($poolName);
        if ($expired = $redis->zrevrangebyscore($from, (string)$now, '-inf', $options)) {
            foreach ($expired as $job) {
                if ($redis->zRem($from, $job) > 0 && !empty($to)) {
                    $redis->lPush($to, $job);
                }
            }
        }
    }


}
