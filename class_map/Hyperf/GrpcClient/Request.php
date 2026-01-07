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

namespace Hyperf\GrpcClient;

use Business\Hyperf\Constants\Constant;
use Google\Protobuf\Internal\Message;
use Hyperf\CodeParser\Package;
use Hyperf\Collection\Arr;
use Hyperf\Context\Context;
use Hyperf\Grpc\Parser;
use Swoole\Http2\Request as BaseRequest;

class Request extends BaseRequest
{
    private const DEFAULT_CONTENT_TYPE = 'application/grpc+proto';

    /**
     * @var null|bool
     */
    public $usePipelineRead;

    public function __construct(string $method, ?Message $argument = null, $headers = [])
    {
        $this->method = 'POST';
        $this->headers = array_replace($this->getDefaultHeaders(), $headers);
        $this->path = $method;
        $argument && $this->data = Parser::serializeMessage($argument);
    }

    public function getDefaultHeaders(): array
    {
        $contextHeaders = Context::get(Constant::JSON_RPC_HEADERS_KEY, []);
        $headers = Arr::collapse([
            $contextHeaders,
            [
                'content-type' => self::DEFAULT_CONTENT_TYPE,
                'te' => 'trailers',
                'user-agent' => $this->buildDefaultUserAgent(),
            ]
        ]);
        if (!array_key_exists(Constant::RPC_PROTOCOL_KEY, $headers)) {
            $headers[Constant::RPC_PROTOCOL_KEY] = Constant::JSON_RPC_HTTP_PROTOCOL;
        }
        return $headers;
    }

    private function buildDefaultUserAgent(): string
    {
        $userAgent = 'grpc-php-hyperf/1.0';
        $grpcClientVersion = Package::getPrettyVersion('hyperf/grpc-client');
        if ($grpcClientVersion) {
            $explodedVersions = explode('@', $grpcClientVersion);
            $userAgent .= ' (hyperf-grpc-client/' . $explodedVersions[0] . ')';
        }
        return $userAgent;
    }
}
