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
use Business\Hyperf\Rpc\Grpc\Consumers\GRpcService;
use App\Service\Sso\SsoService;
use GuzzleHttp\RequestOptions;
use Hyperf\Collection\Arr;
use Hyperf\RpcMultiplex\Constant;

use function Hyperf\Support\env;

$registry = [
    'protocol' => 'nacos',
    //    'address' => 'http://' . env('NACOS_HOST', '127.0.0.1') . ':' . env('NACOS_POST', 8848),
    'address' => env('SERVICES_DISCOVERY_NACOS_ADDRESS', 'http://' . env('NACOS_HOST', '127.0.0.1') . ':' . env('NACOS_POST', 8848)),
    'username' => env('SERVICES_DISCOVERY_NACOS_USERNAME', env('NACOS_USERNAME', '')),
    'password' => env('SERVICES_DISCOVERY_NACOS_PASSWORD', env('NACOS_PASSWORD', '')),
    'group_name' => env('SERVICES_DISCOVERY_NACOS_GROUP_NAME', env('NACOS_GROUP_NAME', 'public')),
    'namespace_id' => env('SERVICES_DISCOVERY_NACOS_NAMESPACE_ID', env('NACOS_NAMESPACE_ID', '')),
    'decrypt' => (bool) env('SERVICES_DISCOVERY_DECRYPT', true),
    RequestOptions::PROXY => env('SERVICES_CALL_PROXY'),
];

$options = [
    'connect_timeout' => 60.0,
    'recv_timeout' => 120.0,
    'settings' => [
        // 根据协议不同，区分配置
        'open_eof_split' => true, // TCP Server (适配 jsonrpc 协议)
        'package_eof' => "\r\n", // TCP Server (适配 jsonrpc 协议)
        // 'open_length_check' => true,//TCP Server (适配 jsonrpc-tcp-length-check 协议)
        // 'package_length_type' => 'N',//TCP Server (适配 jsonrpc-tcp-length-check 协议)
        // 'package_length_offset' => 0,//TCP Server (适配 jsonrpc-tcp-length-check 协议)
        // 'package_body_offset' => 4,//TCP Server (适配 jsonrpc-tcp-length-check 协议)
    ],
    // 重试次数，默认值为 2，收包超时不进行重试。暂只支持 JsonRpcPoolTransporter
    'retry_count' => 2,
    // 重试间隔，毫秒
    'retry_interval' => 100,

    // 多路复用客户端数量
    'client_count' => 4,
    // 使用多路复用 RPC 时的心跳间隔，非 numeric 表示不开启心跳
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
    //    'name' => 'CalculatorService',
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

$serviceProviders = [
    'name' => 'JsonRpcHttpService',
    'is_registry' => (bool) env('SERVICES_REGISTER_IS_REGISTRY', false),
    // 这个服务提供者注册到那个服务注册中心
    'registry' => [
        'protocol' => 'nacos',
        'address' => env('SERVICES_REGISTER_NACOS_ADDRESS'),
        'username' => env('SERVICES_REGISTER_NACOS_USERNAME'),
        'password' => env('SERVICES_REGISTER_NACOS_PASSWORD'),
        'group_name' => env('SERVICES_REGISTER_NACOS_GROUP_NAME', 'public'),
        'namespace_id' => env('SERVICES_REGISTER_NACOS_NAMESPACE_ID'),
        'ephemeral' => env('SERVICES_REGISTER_NACOS_EPHEMERAL', true), // 是否临时实例 默认：是
        'decrypt' => (bool) env('SERVICES_REGISTER_NACOS_DECRYPT', false),
        'protect_threshold' => 0,
    ],
];

return [
    'enable' => [
        'discovery' => (bool) env('SERVICES_ENABLE_DISCOVERY', false), // 开启服务发现
        'register' => (bool) env('SERVICES_ENABLE_REGISTER', false), // 开启服务注册
    ],
    // 服务消费者相关配置
    'consumers' => [
        'ChatGptService' => Arr::collapse([
            $consumer,
            [
                'name' => 'ChatGptService',
                // 这个消费者要从哪个服务注册中心获取节点信息，如不配置则不会从服务中心获取节点信息
                //                'registry' => Arr::collapse([
                //                    $registry,
                //                    [
                //                        'protocol' => 'nacos',
                //                        'address' => env('SERVICES_DISCOVERY_NACOS_ADDRESS_AIGC', ('http://' . env('NACOS_HOST_CHATGPT', '127.0.0.1') . ':' . env('NACOS_POST_CHATGPT', 8848))),
                //                        'username' => env('SERVICES_DISCOVERY_NACOS_USERNAME_AIGC', env('NACOS_USERNAME_CHATGPT', '')),
                //                        'password' => env('SERVICES_DISCOVERY_NACOS_PASSWORD_AIGC', env('NACOS_PASSWORD_CHATGPT', '')),
                //                        'group_name' => env('SERVICES_DISCOVERY_NACOS_GROUP_NAME_AIGC', env('NACOS_GROUP_NAME_CHATGPT', 'public')),
                //                        'namespace_id' => env('SERVICES_DISCOVERY_NACOS_NAMESPACE_ID_AIGC', env('NACOS_NAMESPACE_ID_CHATGPT', '')),
                //                        'decrypt' => (bool)env('SERVICES_DISCOVERY_DECRYPT_AIGC', false),
                //                        RequestOptions::PROXY => env('SERVICES_CALL_PROXY_AIGC'),
                //                    ]
                //                ]),
                // 如果没有指定上面的 registry 配置，即为直接对指定的节点进行消费，通过下面的 nodes 参数来配置服务提供者的节点信息
                'nodes' => [
                    ['host' => '3.95.153.149', 'port' => 9102],
                ],
                RequestOptions::PROXY => env('SERVICES_CALL_PROXY_AIGC'),
            ],
        ]),
        'BertService' => Arr::collapse([
            $consumer,
            [
                'name' => 'BertService',
                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                'registry' => Arr::collapse([
                    $registry,
                    [
                        'protocol' => 'nacos',
                        'address' => env('SERVICES_DISCOVERY_NACOS_ADDRESS_BERT', 'http://' . env('NACOS_HOST_BERT', '127.0.0.1') . ':' . env('NACOS_POST_BERT', 8848)),
                        'username' => env('SERVICES_DISCOVERY_NACOS_USERNAME_BERT', env('NACOS_USERNAME_BERT', '')),
                        'password' => env('SERVICES_DISCOVERY_NACOS_PASSWORD_BERT', env('NACOS_PASSWORD_BERT', '')),
                        'group_name' => env('SERVICES_DISCOVERY_NACOS_GROUP_NAME_BERT', env('NACOS_GROUP_NAME_BERT', 'public')),
                        'namespace_id' => env('SERVICES_DISCOVERY_NACOS_NAMESPACE_ID_BERT', env('NACOS_NAMESPACE_ID_BERT', '')),
                        'decrypt' => (bool) env('SERVICES_DISCOVERY_DECRYPT_BERT', true),
                        RequestOptions::PROXY => env('SERVICES_CALL_PROXY_BERT'),
                    ],
                ]),
                RequestOptions::PROXY => env('SERVICES_CALL_PROXY_BERT'),
            ],
        ]),
        SsoService::$serviceName => Arr::collapse([
            $consumer,
            [
                'name' => SsoService::$serviceName,
                'protocol' => SsoService::$protocol,

                // 配置项，会影响到 Packer 和 Transporter
                'options' => Arr::collapse([
                    $options,
                    [
                        'settings' => [
                            // 根据协议不同，区分配置
                            'package_max_length' => 1024 * 1024 * 2, // 包体最大值，若小于 Server 返回的数据大小，则会抛出异常，故尽量控制包体大小
                        ],
                    ],
                ]),

                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                'registry' => Arr::collapse([
                    $registry,
                    [
                        'protocol' => 'nacos',
                        'address' => env('SERVICES_DISCOVERY_NACOS_ADDRESS_SSO', 'http://' . env('NACOS_HOST_SSO', '127.0.0.1') . ':' . env('NACOS_POST_SSO', 8848)),
                        'username' => env('SERVICES_DISCOVERY_NACOS_USERNAME_SSO', env('NACOS_USERNAME_SSO', '')),
                        'password' => env('SERVICES_DISCOVERY_NACOS_PASSWORD_SSO', env('NACOS_PASSWORD_SSO', '')),
                        'group_name' => env('SERVICES_DISCOVERY_NACOS_GROUP_NAME_SSO', env('NACOS_GROUP_NAME_SSO', 'public')),
                        'namespace_id' => env('SERVICES_DISCOVERY_NACOS_NAMESPACE_ID_SSO', env('NACOS_NAMESPACE_ID_SSO', '')),
                        'decrypt' => (bool) env('SERVICES_DISCOVERY_DECRYPT_SSO', true),
                        RequestOptions::PROXY => env('SERVICES_CALL_PROXY_SSO'),
                    ],
                ]),
                RequestOptions::PROXY => env('SERVICES_CALL_PROXY_SSO'),
            ],
        ]),
        'InventoryMonitoringService' => Arr::collapse([
            $consumer,
            [
                'name' => 'InventoryMonitoringService',
                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                'registry' => Arr::collapse([
                    $registry,
                    [
                        'protocol' => 'nacos',
                        'address' => env('SERVICES_DISCOVERY_NACOS_ADDRESS_INVENTORY_MONITORING', 'http://' . env('NACOS_HOST_INVENTORY_MONITORING', '127.0.0.1') . ':' . env('NACOS_POST_INVENTORY_MONITORING', 8848)),
                        'username' => env('SERVICES_DISCOVERY_NACOS_USERNAME_INVENTORY_MONITORING', env('NACOS_USERNAME_INVENTORY_MONITORING', '')),
                        'password' => env('SERVICES_DISCOVERY_NACOS_PASSWORD_INVENTORY_MONITORING', env('NACOS_PASSWORD_INVENTORY_MONITORING', '')),
                        'group_name' => env('SERVICES_DISCOVERY_NACOS_GROUP_NAME_INVENTORY_MONITORING', env('NACOS_GROUP_NAME_INVENTORY_MONITORING', 'public')),
                        'namespace_id' => env('SERVICES_DISCOVERY_NACOS_NAMESPACE_ID_INVENTORY_MONITORING', env('NACOS_NAMESPACE_ID_INVENTORY_MONITORING', '')),
                        'decrypt' => (bool) env('SERVICES_DISCOVERY_DECRYPT_INVENTORY_MONITORING', true),
                        RequestOptions::PROXY => env('SERVICES_CALL_PROXY_INVENTORY_MONITORING'),
                    ],
                ]),
                RequestOptions::PROXY => env('SERVICES_CALL_PROXY_INVENTORY_MONITORING'),
            ],
        ]),
        'JsonRpcHttpService' => Arr::collapse([
            $consumer,
            [
                'name' => 'JsonRpcHttpService',
                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                'registry' => Arr::collapse([
                    $registry,
                    [],
                ]),
                RequestOptions::PROXY => env('SERVICES_CALL_PROXY'),
            ],
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
                            'open_eof_split' => true, // TCP Server (适配 jsonrpc 协议)
                            'package_eof' => "\r\n", // TCP Server (适配 jsonrpc 协议)
                        ],
                    ],
                ]),

                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                'registry' => Arr::collapse([
                    $registry,
                    [],
                ]),
                RequestOptions::PROXY => env('SERVICES_CALL_PROXY'),
            ],
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
                            'open_length_check' => true, // TCP Server (适配 jsonrpc-tcp-length-check 协议)
                            'package_length_type' => 'N', // TCP Server (适配 jsonrpc-tcp-length-check 协议)
                            'package_length_offset' => 0, // TCP Server (适配 jsonrpc-tcp-length-check 协议)
                            'package_body_offset' => 4, // TCP Server (适配 jsonrpc-tcp-length-check 协议)
                            'package_max_length' => 1024 * 1024 * 2,
                        ],
                    ],
                ]),

                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                'registry' => Arr::collapse([
                    $registry,
                    [],
                ]),
                RequestOptions::PROXY => env('SERVICES_CALL_PROXY'),
            ],
        ]),

        'RpcMultiplexService' => Arr::collapse([
            $consumer,
            [
                'name' => 'RpcMultiplexService',
                'protocol' => Constant::PROTOCOL_DEFAULT,

                // 配置项，会影响到 Packer 和 Transporter
                'options' => Arr::collapse([
                    $options,
                    [
                        'settings' => [
                            // 根据协议不同，区分配置
                            'package_max_length' => 1024 * 1024 * 2,
                        ],
                    ],
                ]),

                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                'registry' => Arr::collapse([
                    $registry,
                    [],
                ]),
                RequestOptions::PROXY => env('SERVICES_CALL_PROXY'),
            ],
        ]),

        GRpcService::$serviceName => Arr::collapse([
            $consumer,
            [
                'name' => GRpcService::$serviceName,
                'protocol' => GRpcService::$protocol,

                // 配置项，会影响到 Packer 和 Transporter
                'options' => Arr::collapse([
                    $options,
                    [],
                ]),

                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                'registry' => Arr::collapse([
                    $registry,
                    [],
                ]),
                RequestOptions::PROXY => env('SERVICES_CALL_PROXY'),
            ],
        ]),

        'BusiService' => Arr::collapse([
            $consumer,
            [
                'name' => 'busi.Busi',
                'protocol' => 'grpc',

                // 配置项，会影响到 Packer 和 Transporter
                'options' => Arr::collapse([
                    $options,
                    [],
                ]),

                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                //                'registry' => Arr::collapse([
                //                    $registry,
                //                    [],
                //                ]),
                // 如果没有指定上面的 registry 配置，即为直接对指定的节点进行消费，通过下面的 nodes 参数来配置服务提供者的节点信息
                'nodes' => [
                    ['host' => '192.168.42.134', 'port' => 8003],
                ],
                RequestOptions::PROXY => env('SERVICES_CALL_PROXY'),
            ],
        ]),
    ],
    // 服务提供者相关配置
    'providers' => [],
    'service_providers' => [
        'JsonRpcHttpService' => Arr::collapse([
            $serviceProviders,
            [
                'name' => 'JsonRpcHttpService',
                'is_registry' => (bool) env('SERVICES_REGISTER_IS_REGISTRY', false),
            ],
        ]),
        'JsonRpcService' => Arr::collapse([
            $serviceProviders,
            [
                'name' => 'JsonRpcService',
            ],
        ]),
        'JsonRpcTcpLengthCheckService' => Arr::collapse([
            $serviceProviders,
            [
                'name' => 'JsonRpcTcpLengthCheckService',
            ],
        ]),
        'RpcMultiplexService' => Arr::collapse([
            $serviceProviders,
            [
                'name' => 'RpcMultiplexService',
            ],
        ]),
        'grpc.GRpc' => Arr::collapse([
            $serviceProviders,
            [
                'name' => 'grpc.GRpc',
            ],
        ]),
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
            'uri' => env('SERVICES_REGISTER_NACOS_ADDRESS', 'http://' . env('SERVICES_REGISTER_NACOS_HOST', env('NACOS_HOST', '127.0.0.1')) . ':' . env('SERVICES_REGISTER_NACOS_POST', env('NACOS_POST', 8848))),
            // The nacos host info
            'host' => env('SERVICES_REGISTER_NACOS_HOST', env('NACOS_HOST', '127.0.0.1')),
            'port' => (int) env('SERVICES_REGISTER_NACOS_POST', env('NACOS_POST', 8848)),
            // The nacos account info
            'username' => env('SERVICES_REGISTER_NACOS_USERNAME', env('NACOS_USERNAME', '')),
            'password' => env('SERVICES_REGISTER_NACOS_PASSWORD', env('NACOS_PASSWORD', '')),
            'guzzle' => [
                'config' => null,
            ],
            'group_name' => env('SERVICES_REGISTER_NACOS_GROUP_NAME', env('NACOS_GROUP_NAME', 'public')),
            'namespace_id' => env('SERVICES_REGISTER_NACOS_NAMESPACE_ID', env('NACOS_NAMESPACE_ID', '')),
            'heartbeat' => 5,
            'ephemeral' => env('SERVICES_REGISTER_NACOS_EPHEMERAL', true), // 是否临时实例 默认：是
            'decrypt' => (bool) env('SERVICES_REGISTER_NACOS_DECRYPT', false),
        ],
    ],
    'rpc_service_provider' => [
        'local' => [
            'host' => env('RPC_SERVICE_PROVIDER_HOST'),
        ],
    ],
];
