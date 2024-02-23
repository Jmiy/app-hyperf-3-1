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

namespace Business\Hyperf\Aspect\Hyperf\Coroutine;

use function Hyperf\Support\call;
use function Hyperf\Support\make;
use function Business\Hyperf\Utils\Collection\data_get;
use function Hyperf\Config\config;
use Hyperf\Context\Context;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Coroutine\Coroutine as CoroutineCoroutine;

use Business\Hyperf\Exception\Handler\AppExceptionHandler;
use Swoole\Coroutine as SwooleCoroutine;
use Hyperf\Context\ApplicationContext;
use Hyperf\Engine\Coroutine as Co;
use Throwable;

#[Aspect(classes: [CoroutineCoroutine::class . '::create', CoroutineCoroutine::class . '::printLog'], annotations: [])]
class Coroutine extends AbstractAspect
{
    public function create(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $arguments = $proceedingJoinPoint->arguments;
        $callable = data_get($arguments, 'keys.callable', []);
        $id = CoroutineCoroutine::id();
//        $coroutineId = SwooleCoroutine::create(static function () use ($callable, $id) {
//            try {
//                // 按需复制，禁止复制 Socket，不然会导致 Socket 跨协程调用从而报错。
//                Context::copy($id, config('common.context_copy', []));
//                call($callable);
//            } catch (Throwable $throwable) {
//                try {
//                    make(AppExceptionHandler::class)->log($throwable);
//                } catch (\Throwable $e1) {
//
//                }
//            }
//        });
//        return is_int($coroutineId) ? $coroutineId : -1;

        $coroutine = Co::create(static function () use ($callable, $id) {
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

        try {
            return $coroutine->getId();
        } catch (Throwable) {
            return -1;
        }
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
