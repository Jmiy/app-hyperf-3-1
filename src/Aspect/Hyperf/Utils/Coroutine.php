<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.wiki/3.0/#/zh-cn/aop
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Business\Hyperf\Aspect\Hyperf\Utils;

use Hyperf\Context\Context;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

//use Business\Hyperf\Kernel\Context\Coroutine as Co;
//use Hyperf\Utils\ApplicationContext;
use Business\Hyperf\Exception\Handler\AppExceptionHandler;
use Swoole\Coroutine as SwooleCoroutine;
use Throwable;

/**
 * @Aspect
 */
#[Aspect(classes: [\Hyperf\Coroutine\Coroutine::class . '::create', \Hyperf\Coroutine\Coroutine::class . '::printLog'], annotations: [])]
class Coroutine extends AbstractAspect
{
    public function create(ProceedingJoinPoint $proceedingJoinPoint)
    {
//        $callable = $proceedingJoinPoint->getArguments();
//        return ApplicationContext::getContainer()->get(Co::class)->create(...$callable);

        $arguments = $proceedingJoinPoint->arguments;
        $callable = data_get($arguments, 'keys.callable', []);
        $id = \Hyperf\Coroutine\Coroutine::id();
        $result = SwooleCoroutine::create(static function () use ($callable, $id) {
            try {
                // 按需复制，禁止复制 Socket，不然会导致 Socket 跨协程调用从而报错。
                $keys = config('common.context_copy', []);
                $keys[] = 'json-rpc-headers';
                Context::copy($id, $keys);
                call($callable);
            } catch (Throwable $throwable) {
                try {
                    make(AppExceptionHandler::class)->log($throwable);
                } catch (Throwable $e1) {

                }
            }
        });
        return is_int($result) ? $result : -1;
    }

    public function printLog(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $throwable = $proceedingJoinPoint->getArguments();
        try {
            make(AppExceptionHandler::class)->log(...$throwable);
        } catch (Throwable $e1) {

        }
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        return call([$this, $proceedingJoinPoint->methodName], [$proceedingJoinPoint]);
    }


}
