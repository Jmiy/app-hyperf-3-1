<?php

/**
 * Aspect trait
 * User: Jmiy
 * Date: 2021-07-31
 * Time: 20:06
 */

namespace Business\Hyperf\Service\Traits;

trait Aspect
{

    /**
     * 切面配置方法 此方法禁止使用 Business\Hyperf\Annotation\Service 否则会导致死循环
     * @param string $methodName 被aop的方法
     * @return array|mixed
     */
    public static function aspect(string $methodName)
    {
        $aspectMap = [
//            'updateRoleDataPermission' => [
//                //'before' => getJobData(static::class, 'clearCacheService', [], null, ['methodName' => $methodName]),
//                'after' => getJobData(static::class, 'clearCacheService', [], null, ['methodName' => $methodName]),
//            ],
        ];
        return data_get($aspectMap, $methodName);
    }

}
