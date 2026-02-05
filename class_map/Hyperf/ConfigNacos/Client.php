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

namespace Hyperf\ConfigNacos;

use Hyperf\Codec\Json;
use Hyperf\Codec\Xml;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Nacos\Application;
use Hyperf\Nacos\Exception\RequestException;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use function Hyperf\Support\call;

class Client implements ClientInterface
{
    protected ConfigInterface $config;

    protected Application $client;

    protected LoggerInterface $logger;

    public function __construct(protected ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class);
        $this->client = $container->get(NacosClient::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

    public function getClient(): Application
    {
        return $this->client;
    }

    public function pull(): array
    {
        $listener = $this->config->get('config_center.drivers.nacos.listener_config', []);

        $baseUri = $this->client->getConfig()->getBaseUri();
        $username = $this->client->getConfig()->getUsername();
        $password = $this->client->getConfig()->getPassword();
        $decryptDefault = $this->config->get('config_center.drivers.nacos.client.decrypt');

//        $baseUri = $this->config->get('config_center.drivers.nacos.client.uri') ?? $this->client->getConfig()->getBaseUri();
//        $username = $this->config->get('config_center.drivers.nacos.client.username', $this->client->getConfig()->getUsername());
//        $password = $this->config->get('config_center.drivers.nacos.client.password', $this->client->getConfig()->getPassword());
//        $decryptDefault = $this->config->get('config_center.drivers.nacos.client.decrypt');

        try {
            if ($decryptDefault) {
                $baseUri = $baseUri === null ? $baseUri : (true === $decryptDefault ? decrypt($baseUri) : call($decryptDefault, [$baseUri]));
                $username = $username === null ? $username : (true === $decryptDefault ? decrypt($username) : call($decryptDefault, [$username]));
                $password = $password === null ? $password : (true === $decryptDefault ? decrypt($password) : call($decryptDefault, [$password]));
            }
        } catch (Throwable $throwable) {
        }
        $this->client->getConfig()->baseUri = $baseUri;
        $this->client->getConfig()->username = $username;
        $this->client->getConfig()->password = $password;

        $config = [];
        foreach ($listener as $key => $item) {

            $address = $item['address'] ?? null;
            $consumerUsername = $item['username'] ?? null;
            $consumerPassword = $item['password'] ?? null;
            $tenant = $item['tenant'] ?? null;
            $decrypt = $item['decrypt'] ?? null;

            try {
                if ($decrypt) {
                    $address = $address === null ? $address : (true === $decrypt ? decrypt($address) : call($decrypt, [$address]));
                    $consumerUsername = $consumerUsername === null ? $consumerUsername : (true === $decrypt ? decrypt($consumerUsername) : call($decrypt, [$consumerUsername]));
                    $consumerPassword = $consumerPassword === null ? $consumerPassword : (true === $decrypt ? decrypt($consumerPassword) : call($decrypt, [$consumerPassword]));
                    $tenant = $tenant === null ? $tenant : (true === $decrypt ? decrypt($tenant) : call($decrypt, [$tenant]));
                }
            } catch (Throwable $throwable) {
            }

            if ($address !== null) {
                $this->client->getConfig()->baseUri = $address;
            }
            if ($consumerUsername !== null) {
                $this->client->getConfig()->username = $consumerUsername;
            }
            if ($consumerPassword !== null) {
                $this->client->getConfig()->password = $consumerPassword;
            }

//            var_dump(__METHOD__,
//                $this->client->getConfig()->getBaseUri(),
//                $this->client->getConfig()->getUsername(),
//                $this->client->getConfig()->getPassword(),
//            );

            try {
                $dataId = $item['data_id'] ?? '';
                $group = $item['group'] ?? '';

                $type = $item['type'] ?? null;
                $response = $this->client->config->get($dataId, $group, $tenant);
                if ($response->getStatusCode() !== 200) {
                    $this->logger->error(sprintf('The config of %s read failed from Nacos.==>' . $response->getStatusCode(), $key));
                    continue;
                }
                $config[$key] = $this->decode((string) $response->getBody(), $type);
            } catch (Throwable $throwable) {
                throw $throwable;
            } finally {
                if ($address !== null) {
                    $this->client->getConfig()->baseUri = $baseUri;
                }
                if ($consumerUsername !== null) {
                    $this->client->getConfig()->username = $username;
                }
                if ($consumerPassword !== null) {
                    $this->client->getConfig()->password = $password;
                }
            }

        }

        return $config;
    }

    public function decode(string $body, ?string $type = null): array|string
    {
        $type = strtolower((string) $type);

        return match ($type) {
            'json' => Json::decode($body),
            'yml', 'yaml' => yaml_parse($body),
            'xml' => Xml::toArray($body),
            default => $body,
        };
    }

    public function getValidNodes(
        string $serviceName,
        #[ArrayShape([
            'groupName' => 'string',
            'namespaceId' => 'string',
            'clusters' => 'string', // 集群名称(字符串，多个集群用逗号分隔)
            'healthyOnly' => 'bool',
        ])]
        array $optional = []
    ): array {
        $response = $this->client->instance->list($serviceName, $optional);
        if ($response->getStatusCode() !== 200) {
            throw new RequestException((string) $response->getBody(), $response->getStatusCode());
        }

        $data = Json::decode((string) $response->getBody());
        $hosts = $data['hosts'] ?? [];
        return array_filter($hosts, function ($item) {
            return $item['valid'] ?? false;
        });
    }
}
