<?php

declare(strict_types=1);

namespace Business\Hyperf;

//use Business\Hyperf\Process\RestartServiceProcess;
use Business\Hyperf\Utils\Redis\Lua\LuaFactory;
use Business\Hyperf\Utils\Redis\Lua\Contracts\LuaInterface;
use Hyperf\Database\Schema\PostgresBuilder;
use Hyperf\Database\Schema\Grammars\PostgresGrammar as SchemaGrammar;
use Hyperf\Database\Query\Processors\PostgresProcessor;
use Hyperf\Database\Query\Grammars\PostgresGrammar;
use Hyperf\Database\PostgresConnection;
use Hyperf\Database\Connectors\PostgresConnector;
use GuzzleHttp\Client;
use Hyperf\Cache\Driver\RedisDriver;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\Database\Model\SoftDeletingScope;
use Hyperf\Database\Model\Relations\HasManyThrough;

//use Hyperf\AsyncQueue\Driver\Driver;
use Hyperf\RateLimit\Aspect\RateLimitAnnotationAspect;

use Hyperf\Nacos\Config;
use Hyperf\ConfigNacos\NacosClient as ConfigNacosClient;
use Hyperf\ConfigNacos\Client as ConfigClient;
use Hyperf\ConfigNacos\NacosDriver as ConfigNacosDriver;

use Hyperf\ServiceGovernanceNacos\NacosDriver;
use Hyperf\ServiceGovernanceNacos\Client as ServiceGovernanceNacosClient;

use Hyperf\JsonRpc\JsonRpcHttpTransporter;
use Hyperf\ServiceGovernance\Listener\RegisterServiceListener;

use Hyperf\Redis\Pool\RedisPool;
use Hyperf\Coroutine\Concurrent as CoroutineConcurrent;

use OSS\Model\ObjectVersionListInfo;
use OSS\Signer\SignerV1;
use OSS\Signer\SignerV4;
use Hyperf\AsyncQueue\Driver\RedisDriver as AsyncQueueRedisDriver;



class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                LuaInterface::class => LuaFactory::class,
                //EncrypterInterface::class => EncrypterFactory::class,
                'db.connector.pgsql' => PostgresConnector::class,
            ],
            'processes' => [
                //RestartServiceProcess::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                    'class_map' => [
                        // 需要映射的类名 => 类所在的文件地址
//                        PostgresBuilder::class => __DIR__ . '/../class_map/Hyperf/Database/Schema/PostgresBuilder.php',
//                        SchemaGrammar::class => __DIR__ . '/../class_map/Hyperf/Database/Schema/PostgresGrammar.php',
//                        PostgresProcessor::class => __DIR__ . '/../class_map/Hyperf/Database/Query/Processors/PostgresProcessor.php',
//                        PostgresGrammar::class => __DIR__ . '/../class_map/Hyperf/Database/Query/Grammars/PostgresGrammar.php',
//                        PostgresConnection::class => __DIR__ . '/../class_map/Hyperf/Database/PostgresConnection.php',
//                        PostgresConnector::class => __DIR__ . '/../class_map/Hyperf/Database/Connectors/PostgresConnector.php',

                        // 需要映射的类名 => 类所在的文件地址
                        SoftDeletes::class => __DIR__ . '/../class_map/Hyperf/Database/Model/SoftDeletes.php',
                        SoftDeletingScope::class => __DIR__ . '/../class_map/Hyperf/Database/Model/SoftDeletingScope.php',
                        HasManyThrough::class => __DIR__ . '/../class_map/Hyperf/Database/Model/Relations/HasManyThrough.php',

                        Client::class => __DIR__ . '/../class_map/GuzzleHttp/Client.php',
                        RedisDriver::class => __DIR__ . '/../class_map/Hyperf/Cache/Driver/RedisDriver.php',
//                        Driver::class => __DIR__ . '/../class_map/Hyperf/AsyncQueue/Driver/Driver.php',
                        AsyncQueueRedisDriver::class => __DIR__ . '/../class_map/Hyperf/AsyncQueue/Driver/RedisDriver.php',

//                        Concurrent::class => __DIR__ . '/../class_map/Hyperf/Utils/Coroutine/Concurrent.php',
                        CoroutineConcurrent::class => __DIR__ . '/../class_map/Hyperf/Coroutine/Concurrent.php',

                        RateLimitAnnotationAspect::class => __DIR__ . '/../class_map/Hyperf/RateLimit/Aspect/RateLimitAnnotationAspect.php',

                        ConfigNacosClient::class => __DIR__ . '/../class_map/Hyperf/ConfigNacos/NacosClient.php',
                        ConfigClient::class => __DIR__ . '/../class_map/Hyperf/ConfigNacos/Client.php',
                        ConfigNacosDriver::class => __DIR__ . '/../class_map/Hyperf/ConfigNacos/NacosDriver.php',

                        RegisterServiceListener::class => __DIR__ . '/../class_map/Hyperf/ServiceGovernance/Listener/RegisterServiceListener.php',
                        Config::class => __DIR__ . '/../class_map/Hyperf/Nacos/Config.php',
                        NacosDriver::class => __DIR__ . '/../class_map/Hyperf/ServiceGovernanceNacos/NacosDriver.php',
                        ServiceGovernanceNacosClient::class => __DIR__ . '/../class_map/Hyperf/ServiceGovernanceNacos/Client.php',


//                        RedisPool::class => __DIR__ . '/../class_map/Hyperf/Redis/Pool/RedisPool.php',

                        JsonRpcHttpTransporter::class => __DIR__ . '/../class_map/Hyperf/JsonRpc/JsonRpcHttpTransporter.php',

                        ObjectVersionListInfo::class => __DIR__ . '/../class_map/OSS/Model/ObjectVersionListInfo.php',
                        SignerV1::class => __DIR__ . '/../class_map/OSS/Signer/SignerV1.php',
                        SignerV4::class => __DIR__ . '/../class_map/OSS/Signer/SignerV4.php',

                    ],
                ],
            ],
            'publish' => [
//                [
//                    'id' => 'apollo-config',
//                    'description' => 'The config for apollo',
//                    'source' => __DIR__ . '/../publish/apollo.php',
//                    'destination' => BASE_PATH . '/config/autoload/apollo.php',
//                ],
//                [
//                    'id' => 'restart-console-config',
//                    'description' => 'The config for restart process',
//                    'source' => __DIR__ . '/../publish/restart_console.php',
//                    'destination' => BASE_PATH . '/config/autoload/restart_console.php',
//                ],
//                [
//                    'id' => 'restart-process-script',
//                    'description' => 'The script for restart process',
//                    'source' => __DIR__ . '/../publish/bin/restart.php',
//                    'destination' => BASE_PATH . '/bin/restart.php',
//                ],
                [
                    'id' => 'async-queue-config',
                    'description' => 'The config for async queue.',
                    'source' => __DIR__ . '/../publish/async_queue.php',
                    'destination' => BASE_PATH . '/config/autoload/async_queue.php',
                ],
                [
                    'id' => 'signal-config',
                    'description' => 'The config for signal.',
                    'source' => __DIR__ . '/../publish/signal.php',
                    'destination' => BASE_PATH . '/config/autoload/signal.php',
                ],
                [
                    'id' => 'snowflake-config',
                    'description' => 'The config of snowflake.',
                    'source' => __DIR__ . '/../publish/snowflake.php',
                    'destination' => BASE_PATH . '/config/autoload/snowflake.php',
                ],
                [
                    'id' => 'ding-config',
                    'description' => 'The config for ding.',
                    'source' => __DIR__ . '/../publish/ding.php',
                    'destination' => BASE_PATH . '/config/autoload/ding.php',
                ],
                [
                    'id' => 'exceptions-config',
                    'description' => 'The config for exceptions.',
                    'source' => __DIR__ . '/../publish/exceptions.php',
                    'destination' => BASE_PATH . '/config/autoload/exceptions.php',
                ],
                [
                    'id' => 'monitor-config',
                    'description' => 'The config for monitor.',
                    'source' => __DIR__ . '/../publish/monitor.php',
                    'destination' => BASE_PATH . '/config/autoload/monitor.php',
                ],
            ],
        ];
    }
}
