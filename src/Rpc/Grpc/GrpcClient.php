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

namespace Business\Hyperf\Rpc\Grpc;

use Google\Protobuf\GPBEmpty;
use Google\Protobuf\Internal\Message;
use Hyperf\GrpcClient\BaseClient;

class GrpcClient extends BaseClient
{
    protected const SERVICE = '/busi.Busi/';

    public function call(
        string $method,
        Message $argument,
        string $replyClass = ''
    ) {
        //        $dd = $this->pathGenerator->generate($this->serviceName, self::SERVICE . ucfirst($method));
        //        var_dump(__METHOD__, self::SERVICE . ucfirst($method),$dd);

        [$reply, $status, $response] = $this->_simpleRequest(
            self::SERVICE . $method,
            $argument,
            [$replyClass ?: GPBEmpty::class, 'decode']
        );

        return [$reply, $status, $response];
    }
}
