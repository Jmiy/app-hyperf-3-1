<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    // 重启服务时等待服务退出的超时时间，超时未退出视为重启失败。单位 秒，可选值 3-60
    'timeout' => (int)env('RESTART_CONSOLE_TIMEOUT', 60),
    // 重启脚本路径
    'script_path' => env('RESTART_CONSOLE_SCRIPT') ?: (BASE_PATH . '/bin/restart.php'),
    // 钉钉警报机器人 webhook access token
    'dingtalk_token' => env('RESTART_CONSOLE_DD_TOKEN', ''),
    // 钉钉警报机器人 secret，适用于 安全设置 勾选 “加签” 模式。“自定义关键词” 和 “IP地址(段)” 模式不需要配置
    'dingtalk_secret' => env('RESTART_CONSOLE_DD_SECRET', ''),
    /**
     * 钉钉警报机器人消息，不配置默认发送 text 消息，消息内容为
     * 重启微服务({APP_NAME})[{HOST_NAME}:{IP_ADDR}]失败，项目路径({APP_PATH})
     *
     * 需要发送其他类型消息的，配置成完整的 json 消息体即可，具体见
     * https://developers.dingtalk.com/document/app/custom-robot-access/title-72m-8ag-pqw#title-72m-8ag-pqw
     *
     * 可使用以下占位符
     * {APP_NAME}  app 名，见 .env APP_NAME
     * {APP_PATH}  即 BASE_PATH
     * {HOST_NAME} 服务器 hostname
     * {IP_ADDR}   服务器本地 IP 地址
     */
    'dingtalk_message' => env('RESTART_CONSOLE_DD_MESSAGE', ''),
];
