<?php

declare(strict_types=1);
/**
 * Job
 */

namespace Business\Hyperf\Job;

//use Business\Hyperf\Service\LogService;
use Hyperf\Context\Context;
use Hyperf\Utils\Coroutine;

use Business\Hyperf\Constants\Constant;

class PublicJob extends Job
{
    public $data;

    /**
     * 任务执行失败后的重试次数，即最大执行次数为 $maxAttempts+1 次
     */
//    protected int $maxAttempts = 1;

    public function __construct($data)
    {
//        data_set($data, 'job_uniqid', getUniqueId(),false);

        // 这里最好是普通数据，不要使用携带 IO 的对象，比如 PDO 对象
        $this->data = $data;

        //设置任务执行失败后的重试次数  默认：1
        //获取规则：
        //1:优先从 $data.Constant::MAX_ATTEMPTS 获取
        //2:再从队列配置中获取  'async_queue.' . $connection . '.' . Constant::MAX_ATTEMPTS
        //3:最后1、2 都没有设置的话，就默认设置为：0
        $connection = data_get($data, Constant::QUEUE_CONNECTION);
        $maxAttempts = data_get(
            $data,
            Constant::MAX_ATTEMPTS,
            config('async_queue.' . $connection . '.' . Constant::MAX_ATTEMPTS)
        );
        if ($maxAttempts !== null) {
            $this->maxAttempts = $maxAttempts;
        }
    }

    public function handle()
    {
        $service = data_get($this->data, Constant::SERVICE, '');
        $method = data_get($this->data, Constant::METHOD, '');
        $parameters = data_get($this->data, Constant::PARAMETERS, []);

        call([$service, $method], $parameters);//兼容各种调用 $service::{$method}(...$parameters);

//        var_dump($this->data);
//        if ($service && $method && method_exists($service, $method)) {
//            try {
//
//                //设置 协程上下文请求数据
//                Context::set(Constant::CONTEXT_REQUEST_DATA, data_get($this->data, Constant::REQUEST_DATA, []));
//
//                call([$service, $method], $parameters);//兼容各种调用 $service::{$method}(...$parameters);
//
//            } catch (\Exception $exc) {
////                $parameters = [
////                    'parameters' => $this->data,
////                    //'exc' => ExceptionHandler::getMessage($exc),
////                ];
////                LogService::addSystemLog('error', $service, $method, 'PublicJob--执行失败', $parameters); //添加系统日志
//            }
//
//            // 根据参数处理具体逻辑
//            // 通过具体参数获取模型等
//            // 这里的逻辑会在 ConsumerProcess 进程中执行
//            //var_dump(Coroutine::parentId(), Coroutine::id(), Coroutine::inCoroutine(), $this->data);
//        }
    }
}
