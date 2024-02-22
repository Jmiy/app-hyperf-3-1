<?php

declare(strict_types=1);

use Hyperf\Process\Handler\ProcessStopHandler;
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki/3.0/#/zh-cn/signal?id=%e6%b7%bb%e5%8a%a0%e5%a4%84%e7%90%86%e5%99%a8
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    'handlers' => [
        ProcessStopHandler::class,//异步队列在终止时，如果正在进行消费逻辑，可能会导致出现错误。框架提供了 ProcessStopHandler ，可以让异步队列进程安全关闭。
//        Hyperf\Signal\Handler\WorkerStopHandler::class => PHP_INT_MIN,//因为 Worker 进程接收的 SIGTERM 信号被捕获后，无法正常退出，所以用户可以直接 Ctrl + C 退出，或者修改 config/autoload/signal.php 配置，如下：
    ],
    'timeout' => 5.0,
];
