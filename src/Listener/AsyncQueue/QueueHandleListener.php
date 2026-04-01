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

use function Hyperf\Support\make;
use function Business\Hyperf\Utils\Collection\data_get;
use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Utils\Monitor\Contract;
use Hyperf\AsyncQueue\AnnotationJob;
use Hyperf\AsyncQueue\Event\AfterHandle;
use Hyperf\AsyncQueue\Event\BeforeHandle;
use Hyperf\AsyncQueue\Event\Event;
use Hyperf\AsyncQueue\Event\FailedHandle;
use Hyperf\AsyncQueue\Event\RetryHandle;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

use Business\Hyperf\Exception\Handler\AppExceptionHandler;
use Business\Hyperf\Job\PublicJob;
use function Hyperf\Config\config;
use function Hyperf\Coroutine\go;

#[Listener]
class QueueHandleListener implements ListenerInterface
{
    protected LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory, protected FormatterInterface $formatter)
    {
        $this->logger = $loggerFactory->get('queue', config('common.loger.queue', 'default'));//
    }

    public function listen(): array
    {
        return [
            AfterHandle::class,
            BeforeHandle::class,
            FailedHandle::class,
            RetryHandle::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof Event && $event->getMessage()->job()) {
            $job = $event->getMessage()->job();
            $jobClass = get_class($job);

            if ($job instanceof PublicJob) {
                $service = data_get($job->data, Constant::SERVICE, '');
                $method = data_get($job->data, Constant::METHOD, '');
                $parameters = data_get($job->data, Constant::PARAMETERS, []);
                $jobClass = sprintf($jobClass . ' [service：%s] [method：%s] [parameters：%s]', $service, $method, json_encode($parameters));
            }

            if ($job instanceof AnnotationJob) {
                $jobClass = sprintf('Job[%s@%s]', $job->class, $job->method);
            }

            switch (true) {
                case $event instanceof BeforeHandle:
                    $this->logger->info(sprintf('BeforeHandle Processing %s.', $jobClass));
                    break;

                case $event instanceof AfterHandle:
                    $this->logger->info(sprintf('AfterHandle Processed %s.', $jobClass));
                    break;

                case $event instanceof FailedHandle:
                    $this->logger->error(sprintf('FailedHandle Processed %s. Throwable：%s', $jobClass, $this->formatter->format($event->getThrowable())));

                    go(function () use ($event) {
                        throw $event->getThrowable();
                    });

                    break;

                case $event instanceof RetryHandle:

                    $this->logger->warning(sprintf('RetryHandle Processed %s. Throwable：%s', $jobClass, $this->formatter->format($event->getThrowable())));

//                    go(function () use ($event) {
//                        throw $event->getThrowable();
//                    });

                    break;
            }
        }
    }

}
