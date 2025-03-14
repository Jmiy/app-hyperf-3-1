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

namespace Hyperf\AsyncQueue\Driver;

use Hyperf\AsyncQueue\Driver\Driver;
use Hyperf\AsyncQueue\Exception\InvalidQueueException;
use Hyperf\AsyncQueue\JobInterface;
use Hyperf\AsyncQueue\JobMessage;
use Hyperf\AsyncQueue\MessageInterface;
use Hyperf\Collection\Arr;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Psr\Container\ContainerInterface;

use function Hyperf\Collection\data_get;
use function Hyperf\Support\make;

class RedisDriver extends Driver
{
    protected RedisProxy $redis;

    protected ChannelConfig $channel;

    /**
     * Max polling time.
     */
    protected int $timeout;

    /**
     * Retry delay time.
     */
    protected array|int $retrySeconds;

    /**
     * Handle timeout.
     */
    protected int $handleTimeout;

    public function __construct(ContainerInterface $container, $config)
    {
        parent::__construct($container, $config);
        $channel = $config['channel'] ?? 'queue';
        $this->redis = $container->get(RedisFactory::class)->get($config['redis']['pool'] ?? 'default');
        $this->timeout = $config['timeout'] ?? 5;
        $this->retrySeconds = $config['retry_seconds'] ?? 10;
        $this->handleTimeout = $config['handle_timeout'] ?? 10;

        $this->channel = make(ChannelConfig::class, ['channel' => $channel]);
    }

    public function push(JobInterface $job, int $delay = 0): bool
    {
        $message = make(JobMessage::class, [$job]);
        $data = $this->packer->pack($message);

        $waitingType = data_get($this->config, ['waiting']);
        if ($waitingType === 'zset' && false !== $this->redis->zScore($this->channel->getReserved(), $data)) {
            return true;
        }

        if ($delay === 0) {
            if ($waitingType === 'zset') {
                return $this->redis->zAdd($this->channel->getWaiting(), time(), $data) > 0;
            } else {
                return (bool)$this->redis->lPush($this->channel->getWaiting(), $data);
            }
        }

        return (bool)$this->redis->zAdd($this->channel->getDelayed(), time() + $delay, $data);
    }

    public function delete(JobInterface $job): bool
    {
        $message = make(JobMessage::class, [$job]);
        $data = $this->packer->pack($message);

        return (bool)$this->redis->zRem($this->channel->getDelayed(), $data);
    }

    public function pop(): array
    {
        $this->move($this->channel->getDelayed(), $this->channel->getWaiting());
        $this->move($this->channel->getReserved(), $this->channel->getTimeout());

        $waitingType = data_get($this->config, ['waiting']);
        if ($waitingType === 'zset') {
            $options = ['LIMIT' => [0, 1]];
            $expired = $this->move($this->channel->getWaiting(), '', $options);

            if (empty($expired)) {
                return [false, null];
            }

            foreach ($expired as $job) {
                $res = [true, $job];
            }
        } else {
            $res = $this->redis->brPop($this->channel->getWaiting(), $this->timeout);
            if (!isset($res[1])) {
                return [false, null];
            }
        }

        $data = $res[1];

        $this->redis->zadd($this->channel->getReserved(), time() + $this->handleTimeout, $data);

        $message = $this->packer->unpack($data);
        if (!$message) {
            return [false, null];
        }

        return [$data, $message];
    }

    public function ack(mixed $data): bool
    {
        return $this->remove($data);
    }

    public function fail(mixed $data): bool
    {
        if ($this->remove($data)) {
            return (bool)$this->redis->lPush($this->channel->getFailed(), (string)$data);
        }
        return false;
    }

    public function reload(?string $queue = null): int
    {
        $channel = $this->channel->getFailed();
        if ($queue) {
            if (!in_array($queue, ['timeout', 'failed'])) {
                throw new InvalidQueueException(sprintf('Queue %s is not supported.', $queue));
            }

            $channel = $this->channel->get($queue);
        }

        $num = 0;

        $waitingType = data_get($this->config, ['waiting']);
        if ($waitingType === 'zset') {
            $listLen = $this->redis->lLen($channel);
            if (empty($listLen)) {
                return $num;
            }

            $res = $this->redis->rpop($channel, $listLen);
            if (empty($res) || !is_array($res)) {//如果待执行队列没有数据了，就跳出整个循环
                return $num;
            }

            $time = time();
            $scoresAndMems = [];
            foreach ($res as $index => $item) {
                $scoresAndMems[] = $time;
                $scoresAndMems[] = $item;
                ++$time;
                ++$num;
            }

            $this->redis->zAdd($this->channel->getWaiting(), ...$scoresAndMems);
        } else {
            while ($this->redis->rpoplpush($channel, $this->channel->getWaiting())) {
                ++$num;
            }
        }

        return $num;
    }

    public function flush(?string $queue = null): bool
    {
        $channel = $this->channel->getFailed();
        if ($queue) {
            $channel = $this->channel->get($queue);
        }

        return (bool)$this->redis->del($channel);
    }

    public function info(): array
    {
        $waitingLen = 0;
        $waitingType = data_get($this->config, ['waiting']);
        if ($waitingType === 'zset') {
            $waitingLen = $this->redis->zCard($this->channel->getWaiting());
        } else {
            $waitingLen = $this->redis->lLen($this->channel->getWaiting());
        }
        return [
//            'waiting' => $this->redis->lLen($this->channel->getWaiting()),
            'waiting' => $waitingLen,
            'delayed' => $this->redis->zCard($this->channel->getDelayed()),
            'failed' => $this->redis->lLen($this->channel->getFailed()),
            'timeout' => $this->redis->lLen($this->channel->getTimeout()),
        ];
    }

    protected function retry(MessageInterface $message): bool
    {
        $data = $this->packer->pack($message);

        $delay = time() + $this->getRetrySeconds($message->getAttempts());

        return (bool)$this->redis->zAdd($this->channel->getDelayed(), $delay, $data);
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
     * Remove data from reserved queue.
     */
    protected function remove(mixed $data): bool
    {
        return (bool)$this->redis->zrem($this->channel->getReserved(), (string)$data);
    }

    /**
     * Move message to the waiting queue.
     */
    protected function move(string $from, string $to, array $options = ['LIMIT' => [0, 100]]): mixed
    {
        $now = time();
//        $options = ['LIMIT' => [0, 100]];
        $options = Arr::collapse([
            ['LIMIT' => [0, 100]],
            $options
        ]);

        $data = [];
        if ($expired = $this->redis->zrevrangebyscore($from, (string)$now, '-inf', $options)) {
            foreach ($expired as $job) {
                if ($this->redis->zRem($from, $job)) {
                    if (!empty($to)) {
                        if ($to == $this->channel->getWaiting() && data_get($this->config, ['waiting']) === 'zset') {
                            $this->redis->zAdd($to, time(), $job);
                        } else {
                            $this->redis->lPush($to, $job);
                        }
                    }
                    $data[] = $job;
                }
            }
        }

        return $data;
    }
}
