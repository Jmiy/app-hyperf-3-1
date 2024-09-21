<?php

namespace Business\Hyperf\Utils\Support\Facades;

use function Hyperf\Config\config;
use function Hyperf\Support\make;
use function Hyperf\Support\call;
use function Business\Hyperf\Utils\Collection\data_get;
use Hyperf\Collection\Arr;
use Hyperf\Coroutine\Concurrent;
use Business\Hyperf\Constants\Constant;
use Hyperf\AsyncQueue\Driver\ChannelConfig;

class QueueRedisDriver
{

    public static function getKey(string|array $connection, string|array $table, array $lockKeys = [])
    {
        $connection = is_array($connection) ? $connection : [$connection];
        array_unshift($connection, config('app_env'));
        array_unshift($connection, config('app_name'));
        return strtolower(implode(':', array_filter(
                    Arr::collapse(
                        [
                            $connection,
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

    public static function getQueueType(string $channel, mixed $queueConnection = null)
    {
        return config('async_queue.' . $queueConnection . '.business.' . $channel);
    }


    public static function push(string $poolName = 'default', string $channel = '', $data = null, int $delay = 0, mixed $queueConnection = null): bool
    {
        $channel = static::getChannelConfig($channel);
        $redis = Redis::getRedis($poolName);

        if ($delay === 0) {

            if (static::getQueueType('waiting', $queueConnection) === 'zset') {
                return $redis->zAdd($channel->getWaiting(), time(), $data) > 0;
            } else {
                return (bool)$redis->lPush($channel->getWaiting(), $data);
            }
        }

        return $redis->zAdd($channel->getDelayed(), time() + $delay, $data) > 0;
    }

    public static function pop(?string $poolName = 'default', ?string $channel = '', $limit = 50, $handleTimeout = 10, mixed $queueConnection = null): mixed
    {
        $channel = static::getChannelConfig($channel);
        $redis = Redis::getRedis($poolName);

        //将延迟队列中到期的消息压入正在执行队列
        static::move($poolName, $channel->getDelayed(), $channel->getWaiting(), $queueConnection);

        //将执行超时的消息压入超时队列
        static::move($poolName, $channel->getReserved(), $channel->getTimeout(), $queueConnection);

        //弹出待执行的消息

        $data = [];
        if (static::getQueueType('waiting', $queueConnection) === 'zset') {
            $data = static::move($poolName, $channel->getWaiting(), '', $queueConnection);
            foreach ($data as $item) {
                //将待执行的消息压入正在执行队列
                $redis->zadd($channel->getReserved(), time() + $handleTimeout, $item);
            }
        } else {
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
        }

        return $data;
    }

    public static function consume(?string $poolName = 'default', ?string $channel = '', int $limit = 50, $handleTimeout = 10, $callBack = null, mixed $queueConnection = null): mixed
    {
        $data = static::pop($poolName, $channel, $limit, $handleTimeout, $queueConnection);

        if (!empty($data)) {
            $callback = static::getCallback($poolName, $channel, $data, $callBack, $queueConnection);

            $concurrent = new Concurrent(10);
            $concurrent->create($callback);
        }

        //将超时队列消息重新入到待执行队列
        static::reload($poolName, $channel, 'timeout', $queueConnection);

        return $data;
    }

    public static function checkQueueLength(?string $poolName = 'default', ?string $channel = '', mixed $queueConnection = null): void
    {
        $info = static::info($poolName, $channel, $queueConnection);
    }

    public static function getCallback(?string $poolName = 'default', ?string $channel = '', $data = [], $callBack = null, mixed $queueConnection = null): callable
    {
        return function () use ($poolName, $channel, $data, $callBack, $queueConnection) {

            $handleCallBack = [
                'ack' => getJobData(static::class, 'ack', [
                        $poolName, $channel, $data, $queueConnection
                    ]
                ),

                'fail' => getJobData(static::class, 'fail', [
                        $poolName, $channel, $data, $queueConnection
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
    public static function delete(?string $poolName = 'default', ?string $channel = '', mixed $data = [], mixed $queueConnection = null): bool
    {
        $redis = Redis::getRedis($poolName);
        $channel = static::getChannelConfig($channel);
        return (bool)$redis->zRem($channel->getDelayed(), $data);
    }

    /**
     * Remove data from reserved queue.
     */
    public static function ack(?string $poolName = 'default', ?string $channel = '', mixed $data = [], mixed $queueConnection = null): bool
    {
        return static::remove($poolName, $channel, $data, $queueConnection);
    }

    /**
     * Remove data from reserved queue.
     */
    public static function remove(?string $poolName = 'default', ?string $channel = '', mixed $data = [], mixed $queueConnection = null): bool
    {
        $redis = Redis::getRedis($poolName);
        $channel = static::getChannelConfig($channel);
        return $redis->zrem($channel->getReserved(), ...$data) > 0;
    }

    /**
     * Remove data from reserved queue.
     * lPush data to failed queue.
     */
    public static function fail(?string $poolName = 'default', ?string $channel = '', mixed $data = [], mixed $queueConnection = null): bool
    {
        $redis = Redis::getRedis($poolName);
        $channel = static::getChannelConfig($channel);
        if (static::remove($poolName, $channel, $data, $queueConnection)) {//Remove data from reserved queue.
            foreach ($data as $item) {
                $redis->lPush($channel->getFailed(), $item);
            }
            return true;
        }

        return false;
    }

    public static function reload(?string $poolName = 'default', ?string $channel = '', string $queue = null, mixed $queueConnection = null): int
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
        if (static::getQueueType('waiting', $queueConnection) === 'zset') {

            $listLen=$redis->lLen($channel);
            if(empty($listLen)){
                return $num;
            }

            $res = $redis->rpop($channel, $listLen);
            if (empty($res)||!is_array($res)) {//如果待执行队列没有数据了，就跳出整个循环
                return $num;
            }

            $scoresAndMems = [];
            $time = time();
            foreach ($res as $index => $item) {
                $scoresAndMems[] = $time + $index;
                $scoresAndMems[] = $item;
                ++$num;
            }

            $redis->zAdd($_channel->getWaiting(), ...$scoresAndMems);
        } else {
            while ($redis->rpoplpush($channel, $_channel->getWaiting())) {
                ++$num;
            }
        }


        return $num;
    }

    public static function flush(?string $poolName = 'default', ?string $channel = '', string $queue = null, mixed $queueConnection = null): bool
    {
        $redis = Redis::getRedis($poolName);
        $_channel = static::getChannelConfig($channel);

        $channel = $_channel->getFailed();
        if ($queue) {
            $channel = $_channel->get($queue);
        }

        return (bool)$redis->del($channel);
    }

    public static function info(?string $poolName = 'default', ?string $channel = '', mixed $queueConnection = null): array
    {
        $redis = Redis::getRedis($poolName);
        $channel = static::getChannelConfig($channel);

        $waitingLen = 0;
        if (static::getQueueType('waiting', $queueConnection) === 'zset') {
            $waitingLen = $redis->zCard($channel->getWaiting());
        } else {
            $waitingLen = $redis->lLen($channel->getWaiting());
        }

        return [
            'waiting' => $waitingLen,
            'delayed' => $redis->zCard($channel->getDelayed()),
            'failed' => $redis->lLen($channel->getFailed()),
            'timeout' => $redis->lLen($channel->getTimeout()),
            'reserved' => $redis->zCard($channel->getReserved()),
        ];
    }

    protected function retry(MessageInterface $message, mixed $queueConnection = null): bool
    {
        $data = $this->packer->pack($message);

        $delay = time() + $this->getRetrySeconds($message->getAttempts());

        return $this->redis->zAdd($this->channel->getDelayed(), $delay, $data) > 0;
    }

    protected function getRetrySeconds(int $attempts, mixed $queueConnection = null): int
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
    public static function move(?string $poolName = 'default', ?string $from = '', ?string $to = '', mixed $queueConnection = null): mixed
    {
        $now = time();
        $options = ['LIMIT' => [0, 100]];
        $redis = Redis::getRedis($poolName);

        /**
         * List elements from a Redis sorted set by score, highest to lowest
         *
         * @param string $key The sorted set to query.
         * @param string $start The highest score to include in the results.
         * @param string $end The lowest score to include in the results.
         * @param array $options An options array that modifies how the command executes.
         *                        <code>
         *                        $options = [
         *                            'WITHSCORES' => true|false # Whether or not to return scores
         *                            'LIMIT' => [offset, count] # Return a subset of the matching members
         *                        ];
         *                        </code>
         *
         *                        NOTE:  For legacy reason, you may also simply pass `true` for the
         *                               options argument, to mean `WITHSCORES`.
         *
         * @return array|false|Redis returns Redis if in multimode
         *
         * @throws RedisException
         * @see zRangeByScore()
         *
         * @example
         * $redis->zadd('oldest-people', 122.4493, 'Jeanne Calment', 119.2932, 'Kane Tanaka',
         *                               119.2658, 'Sarah Knauss',   118.7205, 'Lucile Randon',
         *                               117.7123, 'Nabi Tajima',    117.6301, 'Marie-Louise Meilleur',
         *                               117.5178, 'Violet Brown',   117.3753, 'Emma Morano',
         *                               117.2219, 'Chiyo Miyako',   117.0740, 'Misao Okawa');
         *
         * $redis->zRevRangeByScore('oldest-people', 122, 119);
         * $redis->zRevRangeByScore('oldest-people', 'inf', 118);
         * $redis->zRevRangeByScore('oldest-people', '117.5', '-inf', ['LIMIT' => [0, 1]]);
         */
        if ($expired = $redis->zrevrangebyscore($from, (string)$now, '-inf', $options)) {
            foreach ($expired as $job) {
                if ($redis->zRem($from, $job) > 0 && !empty($to)) {
                    if (false !== strpos($to, ':waiting') && static::getQueueType('waiting', $queueConnection) === 'zset') {
                        $redis->zAdd($to, time(), $job);
                    } else {
                        $redis->lPush($to, $job);
                    }
                }
            }
        }

        return $expired;
    }


}
