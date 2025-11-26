<?php

namespace Business\Hyperf\Utils\Monitor\Ali\Dings;

use Hyperf\Collection\Arr;
use Business\Hyperf\Utils\Support\Facades\Queue;
use Business\Hyperf\Job\DingDingJob;
use Hyperf\Utils\ApplicationContext;
use Hyperf\HttpServer\Contract\RequestInterface;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Ding
{

    /**
     * 配置
     * @param mixed $exceptionName 错误的标题
     * @param mixed $message 错误的信息
     * @param mixed $code 错误的code
     * @param mixed $file 错误的文件
     * @param mixed $line 错误的位置
     * @param mixed $trace 错误的跟踪
     */
    /**
     * @param mixed $exceptionName 错误的标题
     * @param mixed $message 错误的信息
     * @param mixed $code 错误的code
     * @param mixed $file 错误的文件
     * @param mixed $line 错误的位置
     * @param mixed $trace 错误的跟踪
     * @param mixed $robot 机器人配置
     * @param bool|null $simple 是否简述 true:是  false:否  默认：false
     * @param bool|null $isQueue 是否压入消息队列发送 true:是  false:否  默认：true
     * @param int|null $delay 延迟消费时长  默认：null(0-10秒随机)
     * @return bool|null
     */
    public static function report(
        mixed $exceptionName,
        mixed $message,
        mixed $code,
        mixed $file = '',
        mixed $line = '',
        mixed $trace = '',
        mixed $robot = 'default',
        ?bool $simple = false,
        ?bool $isQueue = true,
        ?int  $delay = null
    )
    {
//        $request = ApplicationContext::getContainer()->get(RequestInterface::class);
//        try {
//            $requestData = $request->all();
//            $url = $request->fullUrl() . '|' . $request->getHeaderLine('HTTP_REFERER');
//        } catch (\Exception $ex) {
//            $requestData = [];
//            $url = '';
//        }

        $requestData = [];
        $url = '';

        $trace = Arr::collapse([
            [
                'requestData' => $requestData,
            ],
            (is_array($trace) ? $trace : [$trace])
        ]);

        $dingDingJob = new DingDingJob(
            $url,
            $exceptionName,
            $message,
            $code,
            $file,
            $line,
            $trace,
            $robot,
            $simple
        );
        $delay = $delay === null ? rand(0, 10) : $delay;
        if ($isQueue) {
            return Queue::push($dingDingJob, null, $delay);
        }

        return $dingDingJob->handle();
    }

}
