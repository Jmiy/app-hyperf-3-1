<?php

declare(strict_types=1);

namespace Business\Hyperf\Utils\Service;

use function Hyperf\Coroutine\go;
use Hyperf\Context\ApplicationContext;
use Business\Hyperf\Job\PublicJob;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\AsyncQueue\Annotation\AsyncQueueMessage;
use Business\Hyperf\Utils\Support\Facades\QueueRedisDriver;
use Exception;
use Throwable;
use function Hyperf\Config\config;
use function Business\Hyperf\Utils\Collection\data_get;

class QueueService
{
//    /**
//     * @var DriverInterface
//     */
//    protected $driver;
//
//    public function __construct(DriverFactory $driverFactory)
//    {
//        $this->driver = $driverFactory->get('default');
//    }
//
//    /**
//     * 生产消息.
//     * @param $params 数据
//     * @param int $delay 延时时间 单位秒
//     */
//    public function push($params, int $delay = 0): bool
//    {
//        // 这里的 `PublicJob` 会被序列化存到 Redis 中，所以内部变量最好只传入普通数据
//        // 同理，如果内部使用了注解 @Value 会把对应对象一起序列化，导致消息体变大。
//        // 所以这里也不推荐使用 `make` 方法来创建 `Job` 对象。
//        return $this->driver->push(new PublicJob($params), $delay);
//    }
//
//    /**
//     * 注解方式 直接将 Job 的逻辑搬到 push 方法中，并加上对应注解 AsyncQueueMessage
//     */
//    #[AsyncQueueMessage]
//    public function push($params): bool
//    {
//        // 需要异步执行的代码逻辑
//        // 这里的逻辑会在 ConsumerProcess 进程中执行
//        // 根据参数处理具体逻辑
//        // 通过具体参数获取模型等
//        var_dump($this->params);
//    }
//
//    /**
//     * 注解方式会在非消费环境下自动投递消息到队列，故，如果我们在队列中使用注解方式时，则不会再次投递到队列当中，而是直接在本消费进程中执行。 如果仍然需要在队列中投递消息，则可以在队列中使用传统模式投递。
//     */
//    #[AsyncQueueMessage]
//    public function example($params)
//    {
//        // 需要异步执行的代码逻辑
//        // 这里的逻辑会在 ConsumerProcess 进程中执行
//        var_dump($params);
//    }

    /**
     * 生产消息.
     * @param $params 数据
     * @param int $delay 延时时间 单位秒
     * @param string $queue 消息队列配置名称 默认：default
     * @return bool
     */
    public static function push($params, string $queue = 'default', int $delay = 0): bool
    {
//        var_dump(__METHOD__, func_get_args());
        // 这里的 `PublicJob` 会被序列化存到 Redis 中，所以内部变量最好只传入普通数据
        // 同理，如果内部使用了注解 @Value 会把对应对象一起序列化，导致消息体变大。
        // 所以这里也不推荐使用 `make` 方法来创建 `Job` 对象。
        //return $this->driver->push(new PublicJob($params), $delay);

        $driver = ApplicationContext::getContainer()->get(DriverFactory::class)->get($queue);

        return $driver->push(new PublicJob($params), $delay);
    }

    /**
     * 向管道内写入数据
     * @param array $data
     * @param string $customProcesses
     * @return mixed
     */
    public static function pushQueue($data = [])
    {
        foreach ($data as $item) {
            pushQueue($item);//进入消息队列
        }

        return true;
    }

    /**
     * 检测redis消费队列待处理通道是否为空
     * @param $queueConnection
     * @return bool
     * @throws \Exception
     */
    public static function checkIsEmpty($queueConnection)
    {
        try {
            $redisPoolName = config('async_queue.' . $queueConnection . '.redis.pool');
            $channel = config('async_queue.' . $queueConnection . '.channel');

            //检测redis里是否有待消费队列,如果队列不为空，不做处理
            $readModelInfo = QueueRedisDriver::info($redisPoolName, $channel);
            if (data_get($readModelInfo, 'waiting') != 0
                || data_get($readModelInfo, 'delayed') != 0
                || data_get($readModelInfo, 'timeout') != 0
                || data_get($readModelInfo, 'reserved') != 0
            ) {
                return false;
            }
        } catch (Throwable $e) {
            go(function () use ($e, $queueConnection) {
                throw new Exception('判断队列信息错误：queue:' . $queueConnection . ',msg:' . $e->getMessage());
            });
            return false;
        }

        return true;
    }
}
