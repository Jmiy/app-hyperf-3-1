<?php

namespace Business\Hyperf\Utils;

use Psr\Http\Message\ServerRequestInterface;
use Business\Hyperf\Process\CustomProcess;
use Business\Hyperf\Utils\Service\QueueService;
use Carbon\Carbon;
use Business\Hyperf\Service\ResponseLogService;
use Hyperf\Utils\Arr;
use Business\Hyperf\Constants\Constant;
use Hyperf\Context\Context;
use Hyperf\Utils\Contracts\Arrayable;
use Hyperf\Utils\Contracts\Jsonable;
use Hyperf\HttpServer\Contract\RequestInterface;

class Response
{

    /**
     * 获取统一响应数据结构
     * @param array|Arrayable|Jsonable $data 响应数据
     * @param boolean $isNeedDataKey
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return array 统一响应数据结构
     */
    public static function getResponseData($data = [], $isNeedDataKey = true, $status = Constant::CODE_SUCCESS, array $headers = [], $options = 0)
    {
        return [
            data_get($data, Constant::DATA, $data),
            data_get($data, Constant::CODE, Constant::PARAMETER_INT_DEFAULT),
            data_get($data, Constant::MSG, Constant::PARAMETER_STRING_DEFAULT),
            $isNeedDataKey,
            $status,
            $headers,
            $options,
        ];
    }

    /**
     * Format data to JSON and return data with Content-Type:application/json header.
     *
     * @param array|Arrayable|Jsonable $data
     * @param mix $msg
     * @param int $code
     * @param boolean $isNeedDataKey
     * @param int $status http 状态码
     * @param array $headers 响应头
     * @param int $options
     * @return \Psr\Http\Message\ResponseInterface
     */
    public static function json($data = [], $code = Constant::CODE_SUCCESS, $msg = null, $isNeedDataKey = true, $status = 200, array $headers = [], $options = 0)
    {

        $request = getApplicationContainer()->get(RequestInterface::class);
        $storeId = $request->input('store_id', 0);

        $result = [
            Constant::CODE => $code,
            Constant::MSG => static::getResponseMsg($storeId, $code, $msg),
        ];

        if ($isNeedDataKey) {
            $result[Constant::DATA] = $data;
        } else {
            $result = array_merge($result, $data);
        }

        $serverParams = Context::get(ServerRequestInterface::class)->getServerParams();
        $result[Constant::EXE_TIME] = (number_format(microtime(true) - data_get($serverParams, 'request_time_float', 0), 8, '.', '') * 1000) . ' ms';
        $result['cpu_num'] = swoole_cpu_num();
        $result['service_ip'] = getInternalIp();
//        try {
//
//            $requestData = $request->all();
//
//            data_set($requestData, 'responseData', $result);
//            data_set($requestData, 'responseData.status', $status);
//            data_set($requestData, 'responseData.headers', $headers);
//            data_set($requestData, 'responseData.options', $options);
//
//            //setAppTimezone($appType);
//
//            $action = data_get($requestData, 'account_action', '');
//            $fromUrl = data_get($requestData, Constant::CLIENT_ACCESS_URL, '');
//            $account = data_get($requestData, Constant::DB_COLUMN_ACCOUNT, data_get($requestData, 'help_account', data_get($requestData, 'operator', '')));
//            $cookies = data_get($requestData, 'account_cookies', '').'';
//            $ip = getClientIP(data_get($requestData, Constant::DB_COLUMN_IP, null));
//            $serverParams = $request->getServerParams();
//            $uri = data_get($serverParams,'request_uri');//$request->getRequestUri();
//            $apiUrl = $uri;
//            $createdAt = data_get($requestData, 'created_at', Carbon::now()->toDateTimeString());
//            $extId = data_get($requestData, 'id', 0);
//            $extType = data_get($requestData, 'ext_type', '');
//
//            $parameters = [$action, $storeId, $actId, $fromUrl, $account, $cookies, $ip, $apiUrl, $createdAt, $extId, $extType, $requestData];
//
//            $queueConnection = config('app.log_queue');
//            $extData = [
//                'queueConnectionName' => $queueConnection,//Queue Connection
//                //'queue' => config('async_queue.' . $queueConnection . '.channel'),//Queue Name
//                //'delay' => 1,//任务延迟执行时间  单位：秒
//            ];
//
//            $logTaskData = getJobData(ResponseLogService::getNamespaceClass(), 'addResponseLog', $parameters, null, $extData);
//            $taskData = [
//                $logTaskData,
//            ];
//
//            //QueueService::pushQueue($taskData);
//            $processData = getJobData(QueueService::class, 'pushQueue', [$taskData], []);//
//            CustomProcess::write($processData);
//
//        } catch (\Exception $exc) {
//            //echo $exc->getTraceAsString();
//        }

        return response('http_response', $status, $headers)->json($result);
    }

    /**
     * 获取默认的响应数据结构
     * @param mixed|int $code 响应状态码
     * @param mixed|null $msg 响应提示
     * @param mixed|array $data 响应数据
     * @param int|null $responseStatusCode
     * @param array|null $responseHeaders
     * @return array
     */
    public static function getDefaultResponseData(
        mixed $code = Constant::CODE_SUCCESS,
        mixed $msg = null,
        mixed $data = Constant::PARAMETER_ARRAY_DEFAULT,
        ?int $responseStatusCode = Constant::CODE_SUCCESS,
        ?array $responseHeaders = []
    )
    {
        return [
            Constant::CODE => $code,
            Constant::MSG => $msg,
            Constant::DATA => $data,
            Constant::RESPONSE_STATUS_CODE => $responseStatusCode,
            Constant::RESPONSE_HEADERS => $responseHeaders,
        ];
    }

    /**
     * 获取响应提示
     * @param int $storeId 品牌商店id
     * @param int $code 响应状态码
     * @param string $msg 响应提示 默认：使用系统提示
     * @return string 响应提示
     */
    public static function getResponseMsg($storeId, $code, $msg = null)
    {

        if (!empty($msg)) {
            return $msg;
        }

        $field = PublicValidator::getAttributeName($storeId, $code);
        $validatorData = [
            $field => '',
        ];
        $rules = [
            $field => ['api_code_msg'],
        ];

        $validator = getValidatorFactory()->make($validatorData, $rules);
        $errors = $validator->errors();
        foreach ($rules as $key => $value) {
            if ($errors->has($key)) {
                return $errors->first($key);
            }
        }

        return '';
    }

}
