<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.3.0 版本后，命令行默认开启了事件监听器，所以当有监听器监听了 Command 的事件，且进行了 AMQP 或者其他多路复用的逻辑后，会导致进程无法退出。
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki/3.0/#/zh-cn/upgrade/3.0?id=command
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Business\Hyperf\Listener;

use Hyperf\Command\Event\AfterExecute;
use Hyperf\Coordinator\Constants;
use Hyperf\Coordinator\CoordinatorManager;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class ResumeExitCoordinatorListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            AfterExecute::class,
        ];
    }

    public function process(object $event): void
    {
        CoordinatorManager::until(Constants::WORKER_EXIT)->resume();
    }
}
