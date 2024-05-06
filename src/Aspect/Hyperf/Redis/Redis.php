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

namespace Business\Hyperf\Aspect\Hyperf\Redis;

use function Hyperf\Collection\data_set;
use function Hyperf\Support\call;
use function Business\Hyperf\Utils\Collection\data_get;
use Throwable;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Redis\Redis as HyperfRedis;

#[Aspect(classes: [HyperfRedis::class . '::__call'], annotations: [])]
class Redis extends AbstractAspect
{
    public function aop__call(ProceedingJoinPoint $proceedingJoinPoint)
    {
        return $proceedingJoinPoint->process();
    }

    /**
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return mixed|null
     * @throws \Throwable
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $arguments = data_get($proceedingJoinPoint->arguments, 'keys.arguments');

        $aopProcessCount = data_get($arguments, 'aopProcessCount', 0);
        if (isset($arguments['aopProcessCount'])) {
            unset($arguments['aopProcessCount']);
            data_set($proceedingJoinPoint->arguments, 'keys.arguments', $arguments);
        }

        try {
            return call([$this, 'aop' . $proceedingJoinPoint->methodName], [$proceedingJoinPoint]);
        } catch (Throwable $throwable) {

            if ($aopProcessCount < 10) {

                Coroutine::sleep(rand(1, 5));

                $name = data_get($proceedingJoinPoint->arguments, 'keys.name');
                $arguments['aopProcessCount'] = $aopProcessCount + 1;

                return call([$proceedingJoinPoint->getInstance(), $name], $arguments);
            }

            throw $throwable;
        }

    }
}
