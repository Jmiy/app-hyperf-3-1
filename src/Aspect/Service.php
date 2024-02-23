<?php

declare(strict_types=1);
/**
 * AOP 面向切面编程
 *
 * @link     https://www.hyperf.wiki/3.0/#/zh-cn/aop
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Business\Hyperf\Aspect;

use function Hyperf\Support\call;
use function Business\Hyperf\Utils\Collection\data_get;
use Closure;
use Business\Hyperf\Constants\Constant;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Utils\Arr;
use Business\Hyperf\Annotation\Service AS AnnotationService;

#[Aspect(classes: [], annotations: [AnnotationService::class])]
class Service extends AbstractAspect
{

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {

//        //$proceedingJoinPoint->getAnnotationMetadata(),
//        //$proceedingJoinPoint->processOriginalMethod(),
//        $model = $proceedingJoinPoint->getInstance();
//        //$model->getTable(),$model->getConnectionName(),
//        //$model->getTable(),$model->getConnectionName(),
//        //$proceedingJoinPoint->getReflectMethod(),
//        //$proceedingJoinPoint->processOriginalMethod(),$proceedingJoinPoint->result,
//        //$proceedingJoinPoint->processOriginalMethod(),
//        var_dump($proceedingJoinPoint->className, $proceedingJoinPoint->methodName, $proceedingJoinPoint->getArguments());//

        $arguments = $proceedingJoinPoint->getArguments();
        $methodName = 'aspect';
        $aspectData = [];
        if (method_exists($proceedingJoinPoint->className, $methodName)) {
            $aspectData = call([$proceedingJoinPoint->className, $methodName], [$proceedingJoinPoint->methodName]);
        }

        $before = data_get($aspectData, 'before', []);
        if ($before) {
            $rs = false;
            foreach ($before as $handData) {
                if ($handData instanceof Closure) {
                    $rs = call($handData, [$proceedingJoinPoint]);
                } else {
                    $rs = call([data_get($handData, Constant::SERVICE, ''), data_get($handData, Constant::METHOD, '')], data_get($handData, Constant::PARAMETERS, []));//兼容各种调用
                }

                if (empty($rs)) {
                    break;
                }
            }
        }

        // 切面切入后，执行对应的方法会由此来负责
        // $proceedingJoinPoint 为连接点，通过该类的 process() 方法调用原方法并获得结果
        // 在调用前进行某些处理
        $result = $proceedingJoinPoint->process();

        // 在调用后进行某些处理
        $after = data_get($aspectData, 'after', []);
        if ($after) {

            $rs = false;
            foreach ($after as $handData) {
                if ($handData instanceof Closure) {
                    $rs = call($handData, [$proceedingJoinPoint, $result]);
                } else {
                    $rs = call([data_get($handData, Constant::SERVICE, ''), data_get($handData, Constant::METHOD, '')], data_get($handData, Constant::PARAMETERS, []));//兼容各种调用
                }
                if (empty($rs)) {
                    break;
                }
            }
        }

        return $result;
    }
}
