<?php

declare(strict_types=1);

use function Hyperf\Support\env;
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    // 是否开启配置中心的接入流程，为 true 时会自动启动一个 ConfigFetcherProcess 进程用于更新配置
    'enable' =>  env('APOLLO_ENABLE', false),
    // 是否使用独立进程来拉取config，如果否则将在worker内以协程方式拉取
    'use_standalone_process' => true,
    // Apollo Server
    'server' => env('APOLLO_SERVER', 'http://127.0.0.1:8080'),
    // 您的 AppId
    'appid' => env('APOLLO_APPID', ''),
    // 当前应用所在的集群
    'cluster' => env('APOLLO_CLUSTER', 'default'),
    // 当前应用需要接入的 Namespace，可配置多个
    'namespaces' => explode(',', env('APOLLO_NAMESPACES', 'application')),
    // 配置更新间隔（秒）
    'interval' => 5,
    // 严格模式，当为 false 时，拉取的配置值均为 string 类型，当为 true 时，拉取的配置值会转化为原配置值的数据类型
    'strict_mode' => false,
    // 拉取配置超时时间
    'pull_timeout' => 10,
    // 拉取配置间隔
    'interval_timeout' => 60,
];
