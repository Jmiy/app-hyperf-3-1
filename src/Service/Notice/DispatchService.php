<?php

declare(strict_types=1);

namespace Business\Hyperf\Service\Notice;


use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Service\BaseService;
use function Hyperf\Config\config;
use function Hyperf\Coroutine\go;
use function Business\Hyperf\Utils\Collection\data_get;

class DispatchService extends BaseService
{
    /**
     * 通过消息队列-调度服务-统一入口
     * @param array $extData
     * @return bool|int
     */
    public static function dispatch(array $extData)
    {
        return go(function () use ($extData) {

            $delay = data_get($extData, [Constant::QUEUE_DELAY]);
            $queueConnection = data_get($extData, [Constant::QUEUE_CONNECTION], Constant::QUEUE_CONNECTION_DEFAULT);

            $job = data_get($extData, ['job']);
            $service = data_get($job, [Constant::SERVICE], static::class);
            $method = data_get($job, [Constant::METHOD], 'handleDingTalk');
            $parameters = data_get($job, [Constant::PARAMETERS], []);

            return static::push($service, $method, $parameters, $delay, $queueConnection);
        });
    }

    public static function handleDingTalk(string $robot, array $messages)
    {
        $dingTalk = ding();
        if ($robot !== 'default') {
            $dingTalk->with($robot)->text(implode(PHP_EOL, $messages));
        } else {
            $dingTalk->text(implode(PHP_EOL, $messages));
        }
    }


}
