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

namespace Hyperf\JsonRpc;

use Business\Hyperf\Constants\Constant;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Hyperf\Collection\Arr;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\LoadBalancer\LoadBalancerInterface;
use Hyperf\LoadBalancer\Node;
use Hyperf\Rpc\Contract\TransporterInterface;
use RuntimeException;

use function Business\Hyperf\Utils\Collection\data_get;
use function Hyperf\Config\config;
use function Hyperf\Support\value;

use Hyperf\Context\Context;

class JsonRpcHttpTransporter implements TransporterInterface
{
    private ?LoadBalancerInterface $loadBalancer = null;

    /**
     * If $loadBalancer is null, will select a node in $nodes to request,
     * otherwise, use the nodes in $loadBalancer.
     *
     * @var Node[]
     */
    private array $nodes = [];

    private float $connectTimeout = 5;

    private float $recvTimeout = 5;

    private array $clientOptions;

    public function __construct(private ClientFactory $clientFactory, array $config = [])
    {
        if (!isset($config['recv_timeout'])) {
            $config['recv_timeout'] = $this->recvTimeout;
        }
        if (!isset($config['connect_timeout'])) {
            $config['connect_timeout'] = $this->connectTimeout;
        }
        $this->clientOptions = $config;
    }

    public function send(string $data)
    {
        $node = $this->getNode();
        $uri = $node->host . ':' . $node->port . $node->pathPrefix;
        $schema = value(function () use ($node) {
            $schema = 'http';
            if ($node->schema !== null) {
                $schema = $node->schema;
            }
            if (!in_array($schema, ['http', 'https'])) {
                $schema = 'http';
            }
            $schema .= '://';
            return $schema;
        });
        $url = $schema . $uri;

        /****************AOP handle request options start *****************************/
        $contextHeaders = Context::get(Constant::JSON_RPC_HEADERS_KEY, []);

        $headers = Arr::collapse([
            $contextHeaders,
            [
                'Content-Type' => 'application/json',
//                Constant::RPC_APP_KEY => config('app_name'),
            ]
        ]);

        if (!array_key_exists(Constant::RPC_PROTOCOL_KEY, $headers)) {
            $headers[Constant::RPC_PROTOCOL_KEY] = Constant::JSON_RPC_HTTP_PROTOCOL;
        }

        $options = [
            RequestOptions::HEADERS => $headers,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::BODY => $data,
        ];
        if (array_key_exists('requestOptions', $contextHeaders)) {
            $requestOptions = data_get($contextHeaders, ['requestOptions']);//RequestOptions::PROXY
            if ($requestOptions) {
                $options = Arr::collapse([
                    $options,
                    $requestOptions
                ]);
            }
            unset($headers['requestOptions']);
            $options[RequestOptions::HEADERS] = $headers;
        }
        /****************AOP handle request options end   *****************************/

        $response = $this->getClient()->post($url, $options);
        if ($response->getStatusCode() === 200) {
            return (string)$response->getBody();
        }
//        $this->loadBalancer->removeNode($node);//3.0 bug

        return '';
    }

    public function recv()
    {
        throw new RuntimeException(__CLASS__ . ' does not support recv method.');
    }

    public function getClient(): Client
    {
        $clientOptions = $this->clientOptions;
        // Swoole HTTP Client cannot set recv_timeout and connect_timeout options, use timeout.
        $clientOptions['timeout'] = $clientOptions['recv_timeout'] + $clientOptions['connect_timeout'];
        unset($clientOptions['recv_timeout'], $clientOptions['connect_timeout']);
        return $this->clientFactory->create($clientOptions);
    }

    public function getLoadBalancer(): ?LoadBalancerInterface
    {
        return $this->loadBalancer;
    }

    public function setLoadBalancer(LoadBalancerInterface $loadBalancer): TransporterInterface
    {
        $this->loadBalancer = $loadBalancer;
        return $this;
    }

    /**
     * @param \Hyperf\LoadBalancer\Node[] $nodes
     */
    public function setNodes(array $nodes): self
    {
        $this->nodes = $nodes;
        return $this;
    }

    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function getClientOptions(): array
    {
        return $this->clientOptions;
    }

    /**
     * If the load balancer is exists, then the node will select by the load balancer,
     * otherwise will get a random node.
     */
    private function getNode(): Node
    {
        if ($this->loadBalancer instanceof LoadBalancerInterface) {
            return $this->loadBalancer->select();
        }
        return $this->nodes[array_rand($this->nodes)];
    }
}
