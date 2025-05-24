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

namespace Business\Hyperf\Service\Distributed\Transaction\Dtm;

use App\Service\Cron\TaskService;
use App\Service\Sso\SsoService;
use DtmClient\Annotation\Barrier;
use DtmClient\DbTransaction\DBTransactionInterface;
use DtmClient\TCC;
use DtmClient\TransContext;
use DtmClient\XA;
use Hyperf\Context\Context;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Support\Network;
use Psr\Http\Message\ResponseInterface;
use function Business\Hyperf\Utils\Collection\data_get;
use Hyperf\Collection\Arr;
use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Utils\Response;
use function Hyperf\Config\config;
use function Hyperf\Coroutine\go;
use function Hyperf\Support\call;

#[Controller(prefix: '/distributed/transaction')]
class Contract
{
    #[RequestMapping(path: "getHttpServiceUri", methods: "get,post", options: [
        'aop' => false,
        'auth' => false,
    ])]
    public static function getHttpServiceUri()
    {
        return 'http://' . Network::ip() . ':' . config('server.servers.http.port');
    }

    #[RequestMapping(path: "getGrpcServiceUri", methods: "get,post", options: [
        'aop' => false,
        'auth' => false,
    ])]
    public static function getGrpcServiceUri()
    {
        return Network::ip() . ':' . config('server.servers.grpc.port') . '/busi.Busi/';
    }

    #[RequestMapping(path: "getToken", methods: "get,post", options: [
        'aop' => false,
        'auth' => false,
    ])]
    public static function getToken()
    {
        $id = 103;
        $taskData = TaskService::getTaskData($id, 'Ozon');

        $platformId = data_get($taskData, Constant::DB_COLUMN_PLATFORM_ID);
        $accountId = data_get($taskData, Constant::DB_COLUMN_ACCOUNT_ID);
        $siteId = data_get($taskData, Constant::DB_COLUMN_SITE_ID);
        $accountInfo = data_get($taskData, Constant::DB_COLUMN_ACCOUNT_INFO, []);

        $salesmanId = data_get($accountInfo, Constant::DB_COLUMN_PRIMARY);
        $erpSiteId = data_get($taskData, 'erp_site_id');

        $tokenParameters = [
            Constant::DB_COLUMN_PLATFORM_ID => data_get($accountInfo, ['api_platform_id'], $platformId),
            \App\Constants\Constant::DB_COLUMN_SALESMAN_ID => $salesmanId,
            Constant::DB_COLUMN_SITE_ID => $siteId,
            'erp_site_id' => $erpSiteId,
            'timeout_column' => 'ads_access_token_timeout',
        ];
        if (array_key_exists('is_salesman_id', $accountInfo)) {
            $tokenParameters['is_salesman_id'] = data_get($accountInfo, ['is_salesman_id'], false);
        }
        $tokenData = SsoService::getToken($tokenParameters);

//        var_dump(__METHOD__, Context::get(Constant::CONTEXT_REQUEST_DATA), $tokenData);

        return $tokenData;
    }

    #[RequestMapping(path: "handle", methods: "get,post", options: [
        'aop' => false,
        'auth' => false,
    ])]
    public static function handle($key = ['try'])
    {
        $requestData = Context::get(Constant::CONTEXT_REQUEST_DATA);
        $handler = data_get($requestData, $key);
        if ($handler) {
            $service = data_get($handler, Constant::SERVICE, '');
            $method = data_get($handler, Constant::METHOD, '');
            $parameters = data_get($handler, Constant::PARAMETERS, []);

            return call([$service, $method], $parameters);//兼容各种调用 $service::{$method}(...$parameters);

        }
        return null;
    }

    #[RequestMapping(path: "transaction", methods: "get,post", options: [
        'aop' => false,
        'auth' => false,
    ])]
    public static function transaction(
        array   $handlerData = [
            [
                'try' => [],
                'confirm' => [],
                'cancel' => [],
            ]
        ],
        ?string $distributedTransactionMode = 'TCC',
        ?string $gid = null
    )
    {
        $result = [];
        if ($distributedTransactionMode == 'AX') {
            $xa = getApplicationContainer()->get(XA::class);
            $requestData = Context::get(Constant::CONTEXT_REQUEST_DATA);
            $gid = $gid ?? (data_get($requestData, 'gid') ?? (TransContext::getGid() ?: null));
//            $gid = $gid ?? $xa->generateGid();

            $method = __METHOD__;
            // 开启Xa 全局事物
            $result = $xa->globalTransaction(function () use ($xa, $handlerData, $method) {//XA $xa
                $serviceUri = static::getHttpServiceUri();

                $result = [];
                foreach ($handlerData as $key => $handler) {
                    // 调用子事物接口
                    $respone = $xa->callBranch($serviceUri . '/distributed/transaction/xa/localTransaction', $handler);
                    // XA http模式下获取子事物返回结构

                    $rs = $respone->getBody()->getContents();
                    $result[$key] = json_decode($rs, true) ?? $rs;

//                    var_dump($method, $rs, $result[$key]);
                }

//                var_dump($method, $result);
                return $result;
            }, $gid);

        } else {
            try {
                $result = getApplicationContainer()->get(TCC::class)->globalTransaction(function (TCC $tcc) use ($handlerData) {
                    $serviceUri = static::getHttpServiceUri();

                    $handlerData = array_reverse($handlerData);//反转数组元素
                    $result = null;
                    foreach ($handlerData as $handler) {
                        $respone = $tcc->callBranch(
                            $handler,
                            $serviceUri . '/distributed/transaction/tcc/try',
                            $serviceUri . '/distributed/transaction/tcc/confirm',
                            $serviceUri . '/distributed/transaction/tcc/cancel'
                        );

                        $result[$key] = $respone->getBody()->getContents();

                    }
                }, $gid);
            } catch (Throwable $e) {
                var_dump($e->getMessage(), $e->getTraceAsString());
            }
        }

        return [
            'result' => $result,
            'gid' => TransContext::getGid(),
        ];
    }

    #[RequestMapping(path: "tcc/success", methods: "get,post", options: [
        'aop' => false,
        'auth' => false,
    ])]
    public function successCase()
    {
        try {
            getApplicationContainer()->get(TCC::class)->globalTransaction(function (TCC $tcc) {
                $serviceUri = static::getHttpServiceUri();
                $tcc->callBranch(
                    [
                        'trans_name' => 'trans_A',
                        'try' => getJobData(static::class, 'getToken'),
//                        'confirm' => getJobData(static::class, 'getToken'),
//                        'cancel' => getJobData(static::class, 'getToken'),
                    ],
                    $serviceUri . '/distributed/transaction/tcc/try',
                    $serviceUri . '/distributed/transaction/tcc/confirm',
                    $serviceUri . '/distributed/transaction/tcc/cancel'
                );

                $tcc->callBranch(
                    [
                        'trans_name' => 'trans_B',
//                        'try' => getJobData(static::class, 'getToken'),
//                        'confirm' => getJobData(static::class, 'getToken'),
//                        'cancel' => getJobData(static::class, 'getToken'),
                    ],
                    $serviceUri . '/distributed/transaction/tcc/try',
                    $serviceUri . '/distributed/transaction/tcc/confirm',
                    $serviceUri . '/distributed/transaction/tcc/cancel'
                );
            });
        } catch (Throwable $e) {
            var_dump($e->getMessage(), $e->getTraceAsString());
        }
        return TransContext::getGid();
    }

    #[RequestMapping(path: "tcc/try", methods: "get,post", options: [
        'aop' => false,
        'auth' => false,
    ])]
    public function try(RequestInterface $request): array
    {

        $request->getHeaders();

        static::handle(['try']);

        return [
            'dtm_result' => 'SUCCESS',
        ];
    }

    #[RequestMapping(path: "tcc/confirm", methods: "get,post", options: [
        'aop' => false,
        'auth' => false,
    ])]
    #[Barrier]
    public function confirm(RequestInterface $request): array
    {
        var_dump(__METHOD__, $request->all());

        static::handle(['confirm']);

        return [
            'dtm_result' => 'SUCCESS',
        ];
    }

    #[RequestMapping(path: "tcc/cancel", methods: "get,post", options: [
        'aop' => false,
        'auth' => false,
    ])]
    #[Barrier]
    public function cancel(RequestInterface $request): array
    {
        var_dump(__METHOD__, $request->all());

        static::handle(['cancel']);

        return [
            'dtm_result' => 'SUCCESS',
        ];
    }

    #[RequestMapping(path: "xa/fail", methods: "get,post", options: [
        'aop' => false,
        'auth' => false,
    ])]
    public function xaFail(): array
    {
        var_dump(__METHOD__);
        throw new \Exception('xa==>xaFail', 26655);
        return [];

    }

    #[RequestMapping(path: "xa/localTransaction", methods: "get,post", options: [
        'aop' => false,
        'auth' => false,
    ])]
    public function localTransaction(RequestInterface $request): mixed
    {
//        var_dump(__METHOD__, $request->all());

        $requestData = $request->all();

        // 模拟分布式系统下transOut方法
        $xa = getApplicationContainer()->get(XA::class);
        return $xa->localTransaction(function (DBTransactionInterface $dbTransaction) use ($requestData) {

            // 请使用 DBTransactionInterface 处理本地 Mysql 事物
//            $dbTransaction->xaExecute('UPDATE `order` set `amount` = `amount` - ? where id = 2', [$amount]);
            return static::handle(['handle']);
        });
//        return ['status' => 0, 'message' => 'ok'];
    }

}
