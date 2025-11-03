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

namespace Business\Hyperf\JsonRpc\Consumers;

use Hyperf\Contract\IdGeneratorInterface;
use Hyperf\RpcClient\AbstractServiceClient;
use Hyperf\RpcClient\Exception\RequestException;
use Psr\Container\ContainerInterface;

class BaseServiceClient extends AbstractServiceClient
{

    public function __construct(ContainerInterface $container, $serviceName = '', $protocol = 'jsonrpc-http', $loadBalancer = 'random')
    {
        $this->serviceName = $serviceName;
        $this->protocol = $protocol;
        $this->loadBalancer = $loadBalancer;

        parent::__construct($container);
    }

    public function __request(string $method, array $params, ?string $id = null)
    {
        if (!$id && $this->idGenerator instanceof IdGeneratorInterface) {
            $id = $this->idGenerator->generate();
        }
        $response = $this->client->send($this->__generateData($method, $params, $id));
//        var_dump(__METHOD__, $response);
        if (is_array($response)) {
            $response = $this->checkRequestIdAndTryAgain($response, $id);

            if (array_key_exists('result', $response)) {
                return $response['result'];
            }
            if (array_key_exists('error', $response)) {
                return $response['error'];
            }
        }
        throw new RequestException('Invalid response.');
    }
}


