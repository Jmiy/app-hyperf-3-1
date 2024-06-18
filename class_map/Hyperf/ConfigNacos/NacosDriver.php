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

use Hyperf\Collection\Arr;
use Hyperf\ConfigCenter\AbstractDriver;
use Hyperf\ConfigCenter\Contract\ClientInterface as ConfigClientInterface;
use Hyperf\Nacos\Protobuf\ListenHandler\ConfigChangeNotifyRequestHandler;
use Hyperf\Nacos\Protobuf\Response\ConfigQueryResponse;
use Psr\Container\ContainerInterface;
use Hyperf\ConfigCenter\Mode;
use function Hyperf\Support\call;
use Throwable;

class NacosDriver extends AbstractDriver
{
    protected string $driverName = 'nacos';

    /**
     * @var Client
     */
    protected ConfigClientInterface $client;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->client = $container->get(ClientInterface::class);
    }

    public function createMessageFetcherLoop(): void
    {
        if (!$this->config->get('config_center.drivers.nacos.client.grpc.enable', false)) {
            parent::createMessageFetcherLoop();
            return;
        }

        $baseUri = $this->config->get('config_center.drivers.nacos.client.uri', $this->client->getConfig()->getBaseUri());
        $username = $this->config->get('config_center.drivers.nacos.client.username', $this->client->getConfig()->getUsername());
        $password = $this->config->get('config_center.drivers.nacos.client.password', $this->client->getConfig()->getPassword());
        $decryptDefault = $this->config->get('config_center.drivers.nacos.client.decrypt');
        try {
            if ($decryptDefault) {
                if (true === $decryptDefault) {
                    $baseUri = decrypt($baseUri);
                    $username = decrypt($username);
                    $password = decrypt($password);
                } else {
                    $baseUri = call($decryptDefault, [$baseUri]);
                    $username = call($decryptDefault, [$username]);
                    $password = call($decryptDefault, [$password]);
                }
            }
        } catch (\Throwable $throwable) {
        }
        $this->client->getConfig()->baseUri = $baseUri;
        $this->client->getConfig()->username = $username;
        $this->client->getConfig()->password = $password;

//        $application = $this->client->getClient();

        $_application = [];
        $listeners = $this->config->get('config_center.drivers.nacos.listener_config', []);
        foreach ($listeners as $key => $item) {
            $dataId = $item['data_id'];
            $group = $item['group'];
            $tenant = $item['tenant'] ?? '';
            $type = $item['type'] ?? null;

            /**************兼容配置中心独立配置的情况 start ************************************/
            $address = $item['address'] ?? null;
            $consumerUsername = $item['username'] ?? null;
            $consumerPassword = $item['password'] ?? null;
            $decrypt = $item['decrypt'] ?? null;
            try {
                if ($decrypt) {
                    if (true === $decrypt) {
                        $address = $address !== null ? decrypt($address) : $address;
                        $consumerUsername = $consumerUsername !== null ? decrypt($consumerUsername) : $consumerUsername;
                        $consumerPassword = $consumerPassword !== null ? decrypt($consumerPassword) : $consumerPassword;
                        $tenant = $tenant !== null ? decrypt($tenant) : $tenant;
                    } else {
                        $address = $address !== null ? call($decrypt, [$address]) : $address;
                        $consumerUsername = $consumerUsername !== null ? call($decrypt, [$consumerUsername]) : $consumerUsername;
                        $consumerPassword = $consumerPassword !== null ? call($decrypt, [$consumerPassword]) : $consumerPassword;
                        $tenant = $tenant !== null ? call($decrypt, [$tenant]) : $tenant;
                    }
                }
            } catch (\Throwable $throwable) {
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

            if (!isset($_application[$this->client->getConfig()->getBaseUri()])) {
                $_application[$this->client->getConfig()->getBaseUri()] = $this->client->getClient();
            }
            $application = $_application[$this->client->getConfig()->getBaseUri()];

            if ($address !== null) {
                $this->client->getConfig()->baseUri = $baseUri;
            }
            if ($consumerUsername !== null) {
                $this->client->getConfig()->username = $username;
            }
            if ($consumerPassword !== null) {
                $this->client->getConfig()->password = $password;
            }
            /**************兼容配置中心独立配置的情况 end   ************************************/

            $client = $application->grpc->get($tenant);
            $client->listenConfig($group, $dataId, new ConfigChangeNotifyRequestHandler(function (ConfigQueryResponse $response) use ($key, $type) {
//                $this->updateConfig([
//                    $key => $this->client->decode($response->getContent(), $type),
//                ]);

                $config = [
                    $key => $this->client->decode($response->getContent(), $type),
                ];
                $prevConfig = [$key => $this->config->get($key, [])];

                $this->updateConfig($config);

                $mode = strtolower($this->config->get('config_center.mode', Mode::PROCESS));
                if ($mode === Mode::PROCESS && $config !== $prevConfig) {
                    $this->syncConfig($config, $prevConfig);
                }

            }));
        }

        foreach ($_application as $application) {
            foreach ($application->grpc->getClients() as $client) {
                $client->listen();
            }
        }

//        foreach ($application->grpc->getClients() as $client) {
//            $client->listen();
//        }
    }

    protected function updateConfig(array $config): void
    {
        $root = $this->config->get('config_center.drivers.nacos.default_key');
        foreach ($config as $key => $conf) {
            if (is_int($key)) {
                $key = $root;
            }
            if (is_array($conf) && $this->config->get('config_center.drivers.nacos.merge_mode') === Constants::CONFIG_MERGE_APPEND) {
                $conf = Arr::merge($this->config->get($key, []), $conf);
            }

            $this->config->set($key, $conf);
        }
    }
}
