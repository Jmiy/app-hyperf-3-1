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
    // 是否开启业务异常监控，为 true 时就会通过消息队列将异常，发送到相应的钉钉监控群
    'enable_app_exception_monitor' => env('ENABLE_APP_EXCEPTION_MONITOR', false),
    'app_exception_monitor_platform' => env('APP_EXCEPTION_MONITOR_PLATFORM', [['Ali', 'Ding']]),//,['Tencent', 'WeChat']
];
