<?php

namespace Business\Hyperf\Utils\Support\Facades;

use Hyperf\Collection\Arr;
use GuzzleHttp\Client;
use Hyperf\Guzzle\RetryMiddleware;

class HttpClient
{

    static $pool = [];

    /**
     * 获取http client
     * @param array|null $config GuzzleHttp\Client配置
     * @param array|null $poolHandlerOption 连接池(Hyperf\Guzzle\PoolHandler) 配置
     * @param array|array[]|null $handlerStackMiddlewares 重试中间件，默认：每隔10秒重试一次，重试2次
     * @return Client
     */
    public static function getClient(
        ?array $config = [],
        ?array $poolHandlerOption=[
            'min_connections' => 1,
            'max_connections' => 30,
            'wait_timeout' => 3.0,
            'max_idle_time' => 60,
        ],
        ?array $handlerStackMiddlewares=[
            'retry' => [RetryMiddleware::class, [1, 10]],
        ]
    ): Client
    {

        $name = md5(serialize(func_get_args()));
        if (isset(static::$pool[$name]) && static::$pool[$name]) {
            return static::$pool[$name];
        }

        static::$pool[$name] = make(
            Client::class,
            [
                'config' => Arr::collapse(
                    [
                        [
                            'pool_handler' => [
                                'option' => $poolHandlerOption
                            ],
                            'handler_stack' => [
                                'middlewares' => $handlerStackMiddlewares
                            ],
                        ],
                        $config
                    ]
                )
            ]
        );

        return static::$pool[$name];
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
        return static::getClient()->{$method}(...$args);
    }
}
