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

namespace Business\Hyperf\JsonRpc\Consumers;

use Business\Hyperf\Constants\Constant as BusinessConstant;
use Hyperf\Collection\Arr;
use Hyperf\Retry\Annotation\Retry;

//服务重试: https://hyperf.wiki/3.0/#/zh-cn/retry
use Hyperf\CircuitBreaker\Annotation\CircuitBreaker;
use function Hyperf\Config\config;
use Hyperf\RpcMultiplex\Constant;

//服务熔断及降级: https://hyperf.wiki/3.0/#/zh-cn/circuit-breaker

class RpcMultiplexService extends BaseConsumer
{
    /**
     * The service name of the target service.
     */
    public static string $serviceName = 'RpcMultiplexService';

    /**
     * The protocol of the target service, this protocol name
     * needs to register into \Hyperf\Rpc\ProtocolManager.
     */
    public static string $protocol = Constant::PROTOCOL_DEFAULT;

    /**
     * The load balancer of the client, this name of the load balancer
     * needs to register into \Hyperf\LoadBalancer\LoadBalancerManager.
     */
    public static string $loadBalancer = 'random';

    /**
     * 获取 rpc 上下文
     * @return array
     */
    public static function getRpcContext()
    {
//        $serviceName = config('app_name');
//        $context = [
//            BusinessConstant::RPC_TOKEN_KEY => config('authorization.' . $serviceName . '.' . BusinessConstant::RPC_TOKEN_KEY),
//            'x-jmiy-service' => $serviceName,
//        ];

        //ip限制的场景
//        $context = [
//            BusinessConstant::RPC_TOKEN_KEY => config('authorization.' . $serviceName . '.' . BusinessConstant::RPC_TOKEN_KEY),
//            'x-jmiy-service' => 'product-listing',
//        ];

        //签名认证的场景
//        $context = [
//            BusinessConstant::RPC_TOKEN_KEY => config('authorization.product-listing.' . BusinessConstant::RPC_TOKEN_KEY),
//            'x-jmiy-service' => $serviceName,
//        ];

        $context = [];

        $_context = parent::getRpcContext();

        return Arr::collapse([$_context, $context]);
    }
}


