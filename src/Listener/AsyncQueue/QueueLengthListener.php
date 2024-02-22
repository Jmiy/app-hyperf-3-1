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
namespace Business\Hyperf\Listener\AsyncQueue;

use Hyperf\AsyncQueue\Event\QueueLength;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class QueueLengthListener implements ListenerInterface
{
    protected array $level = [
        'debug' => 10,
        'info' => 50,
        'warning' => 500,
    ];

    public function __construct(protected StdoutLoggerInterface $logger)
    {
    }

    public function listen(): array
    {
        return [
            QueueLength::class,//每处理 500 个消息后触发	用户可以监听此事件，判断失败或超时队列是否有消息积压
        ];
    }

    /**
     * @param QueueLength $event
     */
    public function process(object $event): void
    {
        $value = 0;
        foreach ($this->level as $level => $value) {
            if ($event->length < $value) {
                $message = sprintf('Queue length of %s is %d.', $event->key, $event->length);
                $this->logger->{$level}($message);
                break;
            }
        }

        if ($event->length >= $value) {
            $this->logger->error(sprintf('Queue length of %s is %d.', $event->key, $event->length));
        }
    }
}
