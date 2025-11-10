<?php

declare(strict_types=1);

namespace Business\Hyperf\Service\Notice;


use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Service\BaseService;
use function Hyperf\Config\config;
use function Hyperf\Coroutine\go;
use function Business\Hyperf\Utils\Collection\data_get;
use function Hyperf\Support\call;

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
            $method = data_get($job, [Constant::METHOD], 'dispatchDingTalk');
            $parameters = data_get($job, [Constant::PARAMETERS], []);

            return static::push($service, $method, $parameters, $delay, $queueConnection);
        });
    }

    /**
     * 调度钉钉通知
     * @param string $robot 钉钉配置
     * @param string $method 执行通知的函数
     * @param array $parameters 函数参数
     * @return void
     */
    public static function dispatchDingTalk(string $robot, string $method = 'text', array $parameters = [])
    {
//        $dingTalk = ding();
//        if ($robot !== 'default') {
//            $dingTalk = $dingTalk->with($robot);
//        }

        $dingTalk = ding()->with($robot);

        call([$dingTalk, $method], $parameters);
    }


}
