<?php

namespace Business\Hyperf\Middleware;

use function Hyperf\Collection\data_set;
use function Business\Hyperf\Utils\Collection\data_get;
use Hyperf\Collection\Arr;
use Carbon\Carbon;
use Hyperf\HttpServer\Router\Dispatched;
use Business\Hyperf\Constants\Constant;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Context\Context;

class RequestMiddleware implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $serverParams = $request->getServerParams();
        $uri = data_get($serverParams,'request_uri');//$request->getRequestUri();

        if(false !== stripos($uri, '/favicon.ico')){
            return $handler->handle($request);
        }

        $requestData = $request->getParsedBody()
            +$request->getQueryParams()
//            +$request->getCookieParams()
//            +$request->getUploadedFiles()
//            +$request->getServerParams()
//            +$request->getAttributes()
//            +$request->getHeaders()
        ;

        //var_dump($requestData,$request->getHeaderLine('X-Shopify-Hmac-Sha256'));
        /**
         * "Hyperf\HttpServer\Router\Dispatched" => array:3 [▼
//                "status" => 1
//                "handler" => array:3 [▼
//                    "callback" => array:2 [▼
//                        0 => "Business\Hyperf\Controller\DocController"
//                        1 => "encrypt"
//                    ]
//                    "route" => "/api/shop/encrypt[/{id:\d+}]"
//                    "options" => array:4 [▼
//                        "middleware" => []
//                        "as" => "test_user"
//                        "validator" => array:3 [▼
//                            "type" => "test"
//                            "messages" => []
//                            "rules" => []
//                        ]
//                        "nolog" => "test_nolog"
//                    ]
//                ]
//                "params" => array:1 [▼
//                    "id" => "996"
//                ]
//            ]
         */
        $routeInfo = $request->getAttribute(Dispatched::class);

        if (empty($request->getUploadedFiles())) {//如果不是上传文件，就把原始请求体记录到请求数据中
            data_set($requestData, 'requestBodyContents', $request->getBody()->getContents(), false);
        }

        if (data_get($routeInfo, 'handler')) {
            $routeParameters = data_get($routeInfo, 'params', Constant::PARAMETER_ARRAY_DEFAULT);//获取通过路由传递的参数
            foreach ($routeParameters as $routeKey => $routeParameter) {
                $routeKey = (string)$routeKey;
                if (!(Arr::has($requestData, $routeKey))) {//如果 input 请求参数没有 $routeKey 对应的参数，就将 $routeKey 对应的参数设置到 input 参数中以便后续统一通过 input 获取
                    if ($routeKey == Constant::DATA) {
                        $_data = decrypt($routeParameter);
                        $_data = json_decode($_data, true);
                        foreach ($_data as $key => $value) {
                            data_set($requestData, $key, $value, false);
                        }
                    } else {
                        data_set($requestData, $routeKey, $routeParameter, false);
                    }
                }
            }
        }


        //设置时区
        //setAppTimezone($appType);

        //通过进程间通信 记录请求日志
//        $service = '\Business\Hyperf\Services\LogService';
//        $method = 'addAccessLog';
//        $action = data_get($requestData, 'account_action', data_get($routeInfo, 'handler.options.account_action', ''));
//        data_set($requestData, 'account_action', $action);
//
//        //设置客户访问url
//        $fromUrl = data_get($requestData, Constant::CLIENT_ACCESS_URL, (data_get($headerData,'HTTP_REFERER','no')));
//
//        data_set($requestData, Constant::CLIENT_ACCESS_URL, $fromUrl);
//
//        $account = data_get($requestData, Constant::DB_TABLE_ACCOUNT, data_get($requestData, 'help_account', data_get($requestData, 'operator', '')));
//        $cookies = data_get($requestData, 'account_cookies', '').'';
//        $ip = FunctionHelper::getClientIP(data_get($requestData, Constant::DB_TABLE_IP, null));
//        $apiUrl = $uri;
//        $createdAt = data_get($requestData, 'created_at', Carbon::now()->toDateTimeString());
//        $extId = data_get($requestData, 'id', 0);
//        $extType = data_get($requestData, 'ext_type', '');
//
//        $parameters = [$action, $storeId, $actId, $fromUrl, $account, $cookies, $ip, $apiUrl, $createdAt, $extId, $extType, $requestData];//
//
//        $_parameters = [
//            'apiUrl' => $apiUrl,
//            'storeId' => $storeId,
//            Constant::DB_TABLE_ACCOUNT => $account,
//            'createdAt' => $createdAt,
//        ];
//
//        $queueConnection = config('app.log_queue');
//        $extData = [
//            Constant::QUEUE_CONNECTION => $queueConnection,//Queue Connection
//            //Constant::QUEUE_CHANNEL => config('async_queue.' . $queueConnection . '.channel'),//Queue channel
//            //Constant::QUEUE_DELAY => 1,//任务延迟执行时间  单位：秒
//        ];
//
//        $logTaskData = getJobData($service, $method, $parameters, $requestData, $extData);//
//        $taskData = [
//            $logTaskData
//        ];
//        if ($storeId && $account) {
//            $taskData[] = getJobData(CustomerInfoService::getNamespaceClass(), 'updateLastlogin', [$_parameters], $requestData, $extData);
//        }
//
//        //通过进程间通讯，把写入日志的任务交给自定义进程处理
//        $processData = getJobData(QueueService::class, 'pushQueue', [$taskData], []);//
//        CustomProcess::write($processData);

        data_set($requestData, Constant::CLIENT_ACCESS_API_URI, $uri, false);

        $request = $request->withParsedBody($requestData);

        Context::set('http.request.parsedData', array_merge($request->getParsedBody(), $request->getQueryParams()));//更新 协程上下文请求数据，Request 的请求数据就是从 协程上下文 key 为：http.request.parsedData 中获取的
        $request = Context::set(ServerRequestInterface::class, $request);

        //设置 协程上下文请求数据
        Context::set(Constant::CONTEXT_REQUEST_DATA, $requestData);

        unset($requestData);

        return $handler->handle($request);
    }

}
