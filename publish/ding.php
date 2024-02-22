<?php

use function Hyperf\Support\env;
return [
    'default' => [// 默认发送的机器人
        'enabled' => env('DING_ENABLED', false),// 是否要开启机器人，关闭则不再发送消息
        'token' => env('DING_TOKEN', ''),// 机器人的access_token
        'timeout' => env('DING_TIME_OUT', 2.0),// 钉钉请求的超时时间
        'ssl_verify' => env('DING_SSL_VERIFY', true),// 是否开启ss认证
        'secret' => env('DING_SECRET', ''),// 开启安全配置
    ],
    'other' => [
        'enabled' => env('OTHER_DING_ENABLED', false),// 是否要开启机器人，关闭则不再发送消息
        'token' => env('OTHER_DING_TOKEN', ''),// 机器人的access_token
        'timeout' => env('OTHER_DING_TIME_OUT', 2.0),// 钉钉请求的超时时间
        'ssl_verify' => env('DING_SSL_VERIFY', true),// 是否开启ss认证
        'secret' => env('DING_SECRET', ''),// 开启安全配置
    ]
];
