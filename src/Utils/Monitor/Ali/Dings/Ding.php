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
     * @param string $exceptionName 错误的标题
     * @param string $message 错误的信息
     * @param string $code 错误的code
     * @param string $file 错误的文件
     * @param string $line 错误的位置
     * @param string $trace 错误的跟踪
     */
    public static function report(
        $exceptionName,
        $message,
        $code,
        $file = '',
        $line = '',
        $trace = '',
        $robot = 'default',
        ?bool $simple = false,
        ?bool $isQueue = true
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
        if ($isQueue) {
            return Queue::push($dingDingJob);
        }

        return $dingDingJob->handle();
    }

}
