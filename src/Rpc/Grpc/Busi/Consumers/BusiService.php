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

namespace Business\Hyperf\Rpc\Grpc\Busi\Consumers;

use Business\Hyperf\Constants\Constant as BusinessConstant;
use Business\Hyperf\Rpc\Consumers\BaseConsumer;
use Hyperf\Collection\Arr;
use Hyperf\Grpc\Parser;
use Hyperf\Retry\Annotation\Retry;

// 服务重试: https://hyperf.wiki/3.0/#/zh-cn/retry
use function Hyperf\Config\config;

// 服务熔断及降级: https://hyperf.wiki/3.0/#/zh-cn/circuit-breaker

class BusiService extends BaseConsumer
{
    /**
     * The service name of the target service.
     */
    public static string $serviceName = 'busi.Busi';

    /**
     * The protocol of the target service, this protocol name
     * needs to register into \Hyperf\Rpc\ProtocolManager.
     */
    public static string $protocol = 'grpc';

    /**
     * The load balancer of the client, this name of the load balancer
     * needs to register into \Hyperf\LoadBalancer\LoadBalancerManager.
     */
    public static string $loadBalancer = 'random';

    public static function __callStatic($method, $args)
    {
        $deserialize = $args[0];
        unset($args[0]);

        $args = array_values($args);

        $data = parent::__callStatic($method, $args);

        //        var_dump(__METHOD__, $data);

        if (is_array($data) && array_key_exists('code', $data)) {
            return $data;
        }

        if (count($deserialize) < 2) {
            $deserialize[] = 'decode';
        }

        return Parser::deserializeMessage($deserialize, $data);
    }

    /**
     * 获取 rpc 上下文.
     * @return array
     */
    public static function getRpcContext()
    {
        $context = [];
        //        $serviceName = config('app_name');
        //        $context = [
        //            BusinessConstant::RPC_TOKEN_KEY => config('authorization.' . $serviceName . '1.' . BusinessConstant::RPC_TOKEN_KEY),
        //            'x-jmiy-service' => $serviceName,
        //        ];

        $_context = parent::getRpcContext();

        return Arr::collapse([$_context, $context]);
    }
}
