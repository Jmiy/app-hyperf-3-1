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

use Business\Hyperf\Constants\Constant;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

use Hyperf\HttpServer\Annotation\Controller as AnnotationController;
use Hyperf\HttpServer\Annotation\AutoController;

use Business\Hyperf\Utils\Response;
use Business\Hyperf\Exception\Handler\AppExceptionHandler;
use Hyperf\HttpServer\Annotation\RequestMapping;

#[Aspect(classes: [AppExceptionHandler::class . '::handle'], annotations: [AnnotationController::class, AutoController::class])]
class Controller extends AbstractAspect
{
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        // 切面切入后，执行对应的方法会由此来负责
        // $proceedingJoinPoint 为连接点，通过该类的 process() 方法调用原方法并获得结果
        // 在调用前进行某些处理
        $result = $proceedingJoinPoint->process();

        $annotation = data_get($proceedingJoinPoint->getAnnotationMetadata()->method, RequestMapping::class);
        if ($annotation) {
            $aop = data_get($annotation, 'options.aop', true);
            if ($aop === false) {
                return $result;
            }
        }

        // 在调用后进行某些处理
//        getConfigInterface()->get(LoggerFactory::class)->get('sql')->info(
//            sprintf('[%s] %s', get_class($proceedingJoinPoint->getInstance()), $proceedingJoinPoint->methodName)
//        );

        $isNeedDataKey = true;
        $responseStatusCode = data_get($result, Constant::RESPONSE_STATUS_CODE);
        if ($responseStatusCode !== null) {
            unset($result[Constant::RESPONSE_STATUS_CODE]);
        }
        $responseStatusCode = $responseStatusCode ?? Constant::CODE_SUCCESS;

        $responseHeaders = data_get($result, Constant::RESPONSE_HEADERS);
        if ($responseHeaders !== null) {
            unset($result[Constant::RESPONSE_HEADERS]);
        }
        $responseHeaders = $responseHeaders ?? [];

        return Response::json(...Response::getResponseData($result, $isNeedDataKey, $responseStatusCode, $responseHeaders));
    }
}
