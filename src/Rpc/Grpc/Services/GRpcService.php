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

namespace Business\Hyperf\Rpc\Grpc\Services;

use Business\Hyperf\Rpc\Grpc\Busi\Protobuf\Message\BusiReply;
use Business\Hyperf\Rpc\Grpc\Busi\Protobuf\Message\BusiReq;
use Business\Hyperf\Rpc\Grpc\Protobuf\Message\Reply;
use Business\Hyperf\Rpc\Grpc\Protobuf\Message\Req;
use Business\Hyperf\Service\BaseService;
use Google\Protobuf\Internal\Message;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * 注册服务可通过 #[RpcService] 注解对一个类进行定义，即为发布这个服务了，目前 Hyperf 仅适配了 JSON RPC 协议，具体内容也可到 JSON RPC 服务 章节了解详情。
 * 注意，如希望通过服务中心来管理服务，需在注解内增加 publishTo 属性 protocol="jsonrpc-http", server="jsonrpc-http", publishTo="consul"
 * protocol：目前仅支持 jsonrpc 和 jsonrpc-http 协议发布到服务中心去，其它协议尚未实现服务注册.
 */
//#[RpcService(name: 'grpc.GRpc', server: 'grpc', protocol: 'grpc', publishTo: 'nacos')]
class GRpcService
{
    // 实现一个加法方法，这里简单的认为参数都是 int 类型
    public function add(BusiReq $request): Message
    {
        var_dump(__METHOD__, $request->getAmount());

        $message = new BusiReply();
        $message->setMessage(
            BaseService::pack(
                [
                    'amount' => $request->getAmount(),
                    'a' => __METHOD__,
                ]
            )
        );

        return $message;
    }

    // 实现一个加法方法，这里简单的认为参数都是 int 类型
    public function test(Req $request): Message
    {
        //        throw new \Exception('测试 grpc  异常',9999);

        var_dump(__METHOD__ . '===>new', $request->getData());

        $message = new Reply();
        $message->setData(
            BaseService::pack(
                [
                    'data' => $request->getData(),
                    'a' => __METHOD__,
                ]
            )
        );

        return $message;
        //        return ApplicationContext::getContainer()->get(GPBEmpty::class);
    }

    // 实现一个加法方法，这里简单的认为参数都是 int 类型
    //    public function add(int $a, int $b)
    //    {
    //        // 这里是服务方法的具体实现
    //        return func_get_args();
    //    }
}
