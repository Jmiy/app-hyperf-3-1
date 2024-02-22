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

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

use Business\Hyperf\Annotation\Validator as AnnotationValidator;
use Business\Hyperf\Utils\PublicValidator;

#[Aspect(classes: [], annotations: [AnnotationValidator::class])]
class Validator extends AbstractAspect
{

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $arguments = $proceedingJoinPoint->arguments;
        $argumentData = data_get($arguments, 'keys', []);

        $annotation = $proceedingJoinPoint->getAnnotationMetadata()->method[AnnotationValidator::class];
//        var_dump($annotation->options);

        $rules = [];
        $messages = [];
        $type = '';
        $validatorData = data_get($annotation->options, 'validator', []);
        if ($validatorData) {
            $rules = data_get($validatorData, 'rules', []);
            $messages = data_get($validatorData, 'messages', []);
            $type = data_get($validatorData, 'type', $type);
        }

        $validator = PublicValidator::handle($argumentData, $rules, $messages, $type);
        if ($validator !== true) {//如果验证没有通过就提示用户
            return json_decode($validator->getBody()->getContents(), true);
        }

        return $proceedingJoinPoint->process();
    }
}
