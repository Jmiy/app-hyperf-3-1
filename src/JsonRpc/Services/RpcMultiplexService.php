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

namespace Business\Hyperf\JsonRpc\Services;

use Hyperf\RpcServer\Annotation\RpcService;
use Hyperf\RpcMultiplex\Constant;

/**
 * 注册服务可通过 #[RpcService] 注解对一个类进行定义，即为发布这个服务了，目前 Hyperf 仅适配了 JSON RPC 协议，具体内容也可到 JSON RPC 服务 章节了解详情。
 * 注意，如希望通过服务中心来管理服务，需在注解内增加 publishTo 属性 protocol="jsonrpc-http", server="jsonrpc-http", publishTo="consul"
 * protocol：目前仅支持 jsonrpc 和 jsonrpc-http 协议发布到服务中心去，其它协议尚未实现服务注册
 */
//#[RpcService(name: "RpcMultiplexService", server: "rpc", protocol: Constant::PROTOCOL_DEFAULT, publishTo: "nacos")]
class RpcMultiplexService
{
    // 实现一个加法方法，这里简单的认为参数都是 int 类型
    public function add(int $a, int $b): int
    {
//        var_dump(__METHOD__);
        // 这里是服务方法的具体实现
        return $a + $b;
    }

    // 实现一个加法方法，这里简单的认为参数都是 int 类型
//    public function add(int $a, int $b)
//    {
//        // 这里是服务方法的具体实现
//        return func_get_args();
//    }
}

