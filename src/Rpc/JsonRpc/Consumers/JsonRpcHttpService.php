<?php
declare(strict_types=1);

namespace Business\Hyperf\Rpc\JsonRpc\Consumers;

use Business\Hyperf\Constants\Constant as BusinessConstant;
use Business\Hyperf\Rpc\Consumers\BaseConsumer;
use Hyperf\Collection\Arr;

class JsonRpcHttpService extends BaseConsumer
{

    /**
     * The service name of the target service.
     *
     * @var string
     */
    public static string $serviceName = 'JsonRpcHttpService';

    /**
     * The protocol of the target service, this protocol name
     * needs to register into \Hyperf\Rpc\ProtocolManager.
     *
     * @var string
     */
    public static string $protocol = 'jsonrpc-http';

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