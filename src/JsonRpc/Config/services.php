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

use GuzzleHttp\RequestOptions;
use Hyperf\Collection\Arr;
use function Hyperf\Support\env;

$registry = [
    'protocol' => 'nacos',
    'address' => 'http://' . env('NACOS_HOST', '127.0.0.1') . ':' . env('NACOS_POST', 8848),
];

$options = [
    'connect_timeout' => 60.0,
    'recv_timeout' => 120.0,
    'settings' => [
        // 根据协议不同，区分配置
        'open_eof_split' => true,//TCP Server (适配 jsonrpc 协议)
        'package_eof' => "\r\n",//TCP Server (适配 jsonrpc 协议)
        // 'open_length_check' => true,//TCP Server (适配 jsonrpc-tcp-length-check 协议)
        // 'package_length_type' => 'N',//TCP Server (适配 jsonrpc-tcp-length-check 协议)
        // 'package_length_offset' => 0,//TCP Server (适配 jsonrpc-tcp-length-check 协议)
        // 'package_body_offset' => 4,//TCP Server (适配 jsonrpc-tcp-length-check 协议)
    ],
    // 重试次数，默认值为 2，收包超时不进行重试。暂只支持 JsonRpcPoolTransporter
    'retry_count' => 2,
    // 重试间隔，毫秒
    'retry_interval' => 100,
    // 使用多路复用 RPC 时的心跳间隔，null 为不触发心跳
    'heartbeat' => 30,
    // 当使用 JsonRpcPoolTransporter 时会用到以下配置
    'pool' => [
        'min_connections' => 1,
        'max_connections' => 32,
        'connect_timeout' => 10.0,
        'wait_timeout' => 3.0,
        'heartbeat' => -1,
        'max_idle_time' => 60.0,
    ],
];

$consumer = [
    // name 需与服务提供者的 name 属性相同
//    'name' => 'JsonRpcTcpLengthCheckService',
//    // 服务接口名，可选，默认值等于 name 配置的值，如果 name 直接定义为接口类则可忽略此行配置，如 name 为字符串则需要配置 service 对应到接口类
//    'service' => \App\JsonRpc\Contracts\CalculatorServiceInterface::class,
//    // 对应容器对象 ID，可选，默认值等于 service 配置的值，用来定义依赖注入的 key
//    'id' => \App\JsonRpc\Contracts\CalculatorServiceInterface::class,
    // 服务提供者的服务协议，可选，默认值为 jsonrpc-http
    // 可选 jsonrpc-http jsonrpc jsonrpc-tcp-length-check
    'protocol' => 'jsonrpc-http',
    // 负载均衡算法，可选，默认值为 random
    'load_balancer' => 'random',
    // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
//    'registry' => $registry,
    // 如果没有指定上面的 registry 配置，即为直接对指定的节点进行消费，通过下面的 nodes 参数来配置服务提供者的节点信息
//            'nodes' => [
//                ['host' => '127.0.0.1', 'port' => 9504],
//            ],
    // 配置项，会影响到 Packer 和 Transporter
    'options' => $options,
];

return [
    'enable' => [
        'discovery' => (bool)env('SERVICES_ENABLE_DISCOVERY', false),// 开启服务发现
        'register' => (bool)env('SERVICES_ENABLE_REGISTER', false),// 开启服务注册
    ],
    // 服务消费者相关配置
    'consumers' => [
        'JsonRpcHttpService' => Arr::collapse([
            $consumer,
            [
                'name' => 'JsonRpcHttpService',
                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                'registry' => Arr::collapse([
                    $registry,
                    [
                        'protocol' => 'nacos',
                        'address' => env('SERVICES_DISCOVERY_NACOS_ADDRESS_PRODUCT', ('http://' . env('NACOS_HOST_PRODUCT', '127.0.0.1') . ':' . env('NACOS_POST_PRODUCT', 8848))),
                        'username' => env('SERVICES_DISCOVERY_NACOS_USERNAME_PRODUCT', env('NACOS_USERNAME_PRODUCT', '')),
                        'password' => env('SERVICES_DISCOVERY_NACOS_PASSWORD_PRODUCT', env('NACOS_PASSWORD_PRODUCT', '')),
                        'group_name' => env('SERVICES_DISCOVERY_NACOS_GROUP_NAME_PRODUCT', env('NACOS_GROUP_NAME_PRODUCT', 'public')),
                        'namespace_id' => env('SERVICES_DISCOVERY_NACOS_NAMESPACE_ID_PRODUCT', env('NACOS_NAMESPACE_ID_PRODUCT', '')),
                        'decrypt' => (bool)env('SERVICES_DISCOVERY_DECRYPT_PRODUCT', true),
                        RequestOptions::PROXY => env('SERVICES_CALL_PROXY_PRODUCT'),
                    ]
                ]),
                RequestOptions::PROXY => env('SERVICES_CALL_PROXY_PRODUCT'),
            ]
        ]),
        'JsonRpcService' => Arr::collapse([
            $consumer,
            [
                'name' => 'JsonRpcService',
                'protocol' => 'jsonrpc',

                // 配置项，会影响到 Packer 和 Transporter
                'options' => Arr::collapse([
                    $options,
                    [
                        'settings' => [
                            // 根据协议不同，区分配置
                            'open_eof_split' => true,//TCP Server (适配 jsonrpc 协议)
                            'package_eof' => "\r\n",//TCP Server (适配 jsonrpc 协议)
                        ]
                    ]
                ]),

                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                'registry' => Arr::collapse([
                    $registry,
                    [
                        'protocol' => 'nacos',
                        'address' => env('SERVICES_DISCOVERY_NACOS_ADDRESS_PRODUCT', ('http://' . env('NACOS_HOST_PRODUCT', '127.0.0.1') . ':' . env('NACOS_POST_PRODUCT', 8848))),
                        'username' => env('SERVICES_DISCOVERY_NACOS_USERNAME_PRODUCT', env('NACOS_USERNAME_PRODUCT', '')),
                        'password' => env('SERVICES_DISCOVERY_NACOS_PASSWORD_PRODUCT', env('NACOS_PASSWORD_PRODUCT', '')),
                        'group_name' => env('SERVICES_DISCOVERY_NACOS_GROUP_NAME_PRODUCT', env('NACOS_GROUP_NAME_PRODUCT', 'public')),
                        'namespace_id' => env('SERVICES_DISCOVERY_NACOS_NAMESPACE_ID_PRODUCT', env('NACOS_NAMESPACE_ID_PRODUCT', '')),
                        'decrypt' => (bool)env('SERVICES_DISCOVERY_DECRYPT_PRODUCT', true),
                        RequestOptions::PROXY => env('SERVICES_CALL_PROXY_PRODUCT'),
                    ]
                ]),
                RequestOptions::PROXY => env('SERVICES_CALL_PROXY_PRODUCT'),
            ]
        ]),
        'JsonRpcTcpLengthCheckService' => Arr::collapse([
            $consumer,
            [
                'name' => 'JsonRpcTcpLengthCheckService',
                'protocol' => 'jsonrpc-tcp-length-check',

                // 配置项，会影响到 Packer 和 Transporter
                'options' => Arr::collapse([
                    $options,
                    [
                        'settings' => [
                            // 根据协议不同，区分配置
                            'open_length_check' => true,//TCP Server (适配 jsonrpc-tcp-length-check 协议)
                            'package_length_type' => 'N',//TCP Server (适配 jsonrpc-tcp-length-check 协议)
                            'package_length_offset' => 0,//TCP Server (适配 jsonrpc-tcp-length-check 协议)
                            'package_body_offset' => 4,//TCP Server (适配 jsonrpc-tcp-length-check 协议)
                            'package_max_length' => 1024 * 1024 * 2,
                        ]
                    ]
                ]),

                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                'registry' => Arr::collapse([
                    $registry,
                    [
                        'protocol' => 'nacos',
                        'address' => env('SERVICES_DISCOVERY_NACOS_ADDRESS_PRODUCT', ('http://' . env('NACOS_HOST_PRODUCT', '127.0.0.1') . ':' . env('NACOS_POST_PRODUCT', 8848))),
                        'username' => env('SERVICES_DISCOVERY_NACOS_USERNAME_PRODUCT', env('NACOS_USERNAME_PRODUCT', '')),
                        'password' => env('SERVICES_DISCOVERY_NACOS_PASSWORD_PRODUCT', env('NACOS_PASSWORD_PRODUCT', '')),
                        'group_name' => env('SERVICES_DISCOVERY_NACOS_GROUP_NAME_PRODUCT', env('NACOS_GROUP_NAME_PRODUCT', 'public')),
                        'namespace_id' => env('SERVICES_DISCOVERY_NACOS_NAMESPACE_ID_PRODUCT', env('NACOS_NAMESPACE_ID_PRODUCT', '')),
                        'decrypt' => (bool)env('SERVICES_DISCOVERY_DECRYPT_PRODUCT', true),
                        RequestOptions::PROXY => env('SERVICES_CALL_PROXY_PRODUCT'),
                    ]
                ]),
                RequestOptions::PROXY => env('SERVICES_CALL_PROXY_PRODUCT'),
            ]
        ]),

        'RpcMultiplexService' => Arr::collapse([
            $consumer,
            [
                'name' => 'RpcMultiplexService',
                'protocol' => Hyperf\RpcMultiplex\Constant::PROTOCOL_DEFAULT,

                // 配置项，会影响到 Packer 和 Transporter
                'options' => Arr::collapse([
                    $options,
                    [
                        'settings' => [
                            // 根据协议不同，区分配置
                            'open_length_check' => true,//TCP Server (适配 jsonrpc-tcp-length-check 协议)
                            'package_length_type' => 'N',//TCP Server (适配 jsonrpc-tcp-length-check 协议)
                            'package_length_offset' => 0,//TCP Server (适配 jsonrpc-tcp-length-check 协议)
                            'package_body_offset' => 4,//TCP Server (适配 jsonrpc-tcp-length-check 协议)
                            'package_max_length' => 1024 * 1024 * 2,
                        ]
                    ]
                ]),

                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                'registry' => Arr::collapse([
                    $registry,
                    [
                        'protocol' => 'nacos',
                        'address' => env('SERVICES_DISCOVERY_NACOS_ADDRESS_PRODUCT', ('http://' . env('NACOS_HOST_PRODUCT', '127.0.0.1') . ':' . env('NACOS_POST_PRODUCT', 8848))),
                        'username' => env('SERVICES_DISCOVERY_NACOS_USERNAME_PRODUCT', env('NACOS_USERNAME_PRODUCT', '')),
                        'password' => env('SERVICES_DISCOVERY_NACOS_PASSWORD_PRODUCT', env('NACOS_PASSWORD_PRODUCT', '')),
                        'group_name' => env('SERVICES_DISCOVERY_NACOS_GROUP_NAME_PRODUCT', env('NACOS_GROUP_NAME_PRODUCT', 'public')),
                        'namespace_id' => env('SERVICES_DISCOVERY_NACOS_NAMESPACE_ID_PRODUCT', env('NACOS_NAMESPACE_ID_PRODUCT', '')),
                        'decrypt' => (bool)env('SERVICES_DISCOVERY_DECRYPT_PRODUCT', true),
                        RequestOptions::PROXY => env('SERVICES_CALL_PROXY_PRODUCT'),
                    ]
                ]),
                RequestOptions::PROXY => env('SERVICES_CALL_PROXY_PRODUCT'),
            ]
        ]),

    ],
    // 服务提供者相关配置
    'providers' => [
    ],
    'service_providers' => [
        'JsonRpcHttpService' => [
            'name' => 'JsonRpcHttpService',
            'is_registry' => (bool)env('SERVICES_REGISTER_IS_REGISTRY', true),
            // 这个服务提供者注册到那个服务注册中心
            'registry' => [
                'protocol' => 'nacos',
                'address' => env('SERVICES_REGISTER_NACOS_ADDRESS'),
                'username' => env('SERVICES_REGISTER_NACOS_USERNAME'),
                'password' => env('SERVICES_REGISTER_NACOS_PASSWORD'),
                'group_name' => env('SERVICES_REGISTER_NACOS_GROUP_NAME', 'public'),
                'namespace_id' => env('SERVICES_REGISTER_NACOS_NAMESPACE_ID'),
                'ephemeral' => env('SERVICES_REGISTER_NACOS_EPHEMERAL', true),//是否临时实例 默认：是
                'decrypt' => (bool)env('SERVICES_REGISTER_NACOS_DECRYPT', false),
                'protect_threshold' => 0,
            ],
        ],
        'EmailService' => [
            'name' => 'EmailService',
            'is_registry' => (bool)env('SERVICES_REGISTER_IS_REGISTRY', true),
            // 这个服务提供者注册到那个服务注册中心
            'registry' => [
                'protocol' => 'nacos',
                'address' => env('SERVICES_REGISTER_NACOS_ADDRESS'),
                'username' => env('SERVICES_REGISTER_NACOS_USERNAME'),
                'password' => env('SERVICES_REGISTER_NACOS_PASSWORD'),
                'group_name' => env('SERVICES_REGISTER_NACOS_GROUP_NAME', 'public'),
                'namespace_id' => env('SERVICES_REGISTER_NACOS_NAMESPACE_ID'),
                'ephemeral' => env('SERVICES_REGISTER_NACOS_EPHEMERAL', true),//是否临时实例 默认：是
                'decrypt' => (bool)env('SERVICES_REGISTER_NACOS_DECRYPT', false),
                'protect_threshold' => 0,
            ],
        ],
    ],
    'drivers' => [
        'consul' => [
            'uri' => 'http://127.0.0.1:8500',
            'token' => '',
            'check' => [
                'deregister_critical_service_after' => '90m',
                'interval' => '1s',
            ],
        ],
        'nacos' => [
            // nacos server url like https://nacos.hyperf.io, Priority is higher than host:port
            'uri' => env('SERVICES_REGISTER_NACOS_ADDRESS', ('http://' . env('SERVICES_REGISTER_NACOS_HOST', env('NACOS_HOST', '127.0.0.1')) . ':' . env('SERVICES_REGISTER_NACOS_POST', env('NACOS_POST', 8848)))),
            // The nacos host info
            'host' => env('SERVICES_REGISTER_NACOS_HOST', env('NACOS_HOST', '127.0.0.1')),
            'port' => (int)env('SERVICES_REGISTER_NACOS_POST', env('NACOS_POST', 8848)),
            // The nacos account info
            'username' => env('SERVICES_REGISTER_NACOS_USERNAME', env('NACOS_USERNAME', '')),
            'password' => env('SERVICES_REGISTER_NACOS_PASSWORD', env('NACOS_PASSWORD', '')),
            'guzzle' => [
                'config' => null,
            ],
            'group_name' => env('SERVICES_REGISTER_NACOS_GROUP_NAME', env('NACOS_GROUP_NAME', 'public')),
            'namespace_id' => env('SERVICES_REGISTER_NACOS_NAMESPACE_ID', env('NACOS_NAMESPACE_ID', '')),
            'heartbeat' => 5,
            'ephemeral' => env('SERVICES_REGISTER_NACOS_EPHEMERAL', true),//是否临时实例 默认：是
            'decrypt' => (bool)env('SERVICES_REGISTER_NACOS_DECRYPT', false),

        ],
    ],
    'rpc_service_provider' => [
        'local' => [
            'host' => env('RPC_SERVICE_PROVIDER_HOST'),
        ],
    ]
];
