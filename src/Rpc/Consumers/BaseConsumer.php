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

namespace Business\Hyperf\Rpc\Consumers;

use Business\Hyperf\Constants\Constant as BusinessConstant;
use GuzzleHttp\RequestOptions;
use Hyperf\Collection\Arr;
use Hyperf\Context\Context;
use Hyperf\Contract\IdGeneratorInterface;
use Hyperf\RpcClient\AbstractServiceClient;
use Hyperf\RpcClient\Exception\RequestException;
use Psr\Container\ContainerInterface;
use function Hyperf\Collection\data_get;
use function Hyperf\Config\config;
use function Hyperf\Support\call;
use function Hyperf\Support\make;

use Hyperf\Rpc\Context as RpcContext;

class BaseConsumer
{

    /**
     * The service name of the target service.
     *
     * @var string
     */
    public static string $serviceName = '';

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

    public static $instance = null;

    public static function setHeaders(array $context = [])
    {
        static::setRpcContext($context);
    }

    public static function getInstance()
    {
        if (empty(static::$instance[static::$serviceName][static::$protocol])) {
            static::$instance[static::$serviceName][static::$protocol] = make(
                BaseServiceClient::class,
                [
                    getApplicationContainer(),
                    static::$serviceName,
                    static::$protocol,
                    static::$loadBalancer
                ]
            );
        }

        return static::$instance[static::$serviceName][static::$protocol];
    }

    /**
     * 获取 rpc 上下文
     * @return array
     */
    public static function getRpcContext()
    {
        $serviceName = config('app_name');
        $context = [
            BusinessConstant::RPC_TOKEN_KEY => config('authorization.' . $serviceName . '.' . BusinessConstant::RPC_TOKEN_KEY),
            BusinessConstant::RPC_SERVICE_APP_KEY => $serviceName,//服务提供者
        ];

        return $context;
    }

    public static function setRpcContext($context)
    {
        $contextHeaders = Context::get(BusinessConstant::JSON_RPC_HEADERS_KEY, []);

        $proxy = data_get($context, ['requestOptions', RequestOptions::PROXY]);
        $proxy = $proxy ?: config('services.consumers.' . static::$serviceName . '.registry.' . RequestOptions::PROXY);
        $proxy = $proxy ?: config('services.consumers.' . static::$serviceName . '.' . RequestOptions::PROXY);
        if ($proxy) {
            $context['requestOptions'][RequestOptions::PROXY] = $proxy;
        }

        $contextHeaders = Arr::collapse([
            $contextHeaders,
            $context,
            [
                BusinessConstant::RPC_PROTOCOL_KEY => static::$protocol,
                BusinessConstant::RPC_APP_KEY => config('app_name'),//请求服务的客户端应用
            ]
        ]);

        Context::set(BusinessConstant::JSON_RPC_HEADERS_KEY, $contextHeaders);

        $rpcContext = getApplicationContainer()->get(RpcContext::class)->getData();

        $rpcContext = Arr::collapse([
            $rpcContext,
            $contextHeaders,
        ]);

        getApplicationContainer()->get(RpcContext::class)->setData($rpcContext);
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function __call($method, $args)
    {
        return call([static::class, $method], $args);
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function __callStatic($method, $args)
    {

        $rpcContext = static::getRpcContext();

        static::setRpcContext($rpcContext);

        return static::getInstance()->__request($method, $args);
    }
}


