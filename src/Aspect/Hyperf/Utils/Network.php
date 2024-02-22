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

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Utils\Network as UtilsNetwork;

#[Aspect(classes: [UtilsNetwork::class . '::ip'], annotations: [])]
class Network extends AbstractAspect
{
    public function ip(ProceedingJoinPoint $proceedingJoinPoint)
    {
        //获取本服务的host
        $host = config('services.rpc_service_provider.local.host', null);

        return $host !== null ? $host : $proceedingJoinPoint->process();//本服务的host没有配置，就使用Hyperf框架本身提供的方法(Hyperf\ServiceGovernance\Listener\RegisterServiceListener::getInternalIp)获取
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        return call([$this, $proceedingJoinPoint->methodName], [$proceedingJoinPoint]);
    }


}
