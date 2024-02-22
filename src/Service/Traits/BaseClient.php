<?php

/**
 * Base trait
 * User: Jmiy
 * Date: 2020-09-03
 * Time: 09:27
 */

namespace Business\Hyperf\Service\Traits;

use Hyperf\Collection\Arr;
use Throwable;
use function Hyperf\Coroutine\go;
use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Exception\Handler\AppExceptionHandler;
use Business\Hyperf\Utils\Arrays\MyArr;
use Business\Hyperf\Utils\Support\Facades\HttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

trait BaseClient
{

    /**
     * http请求
     * @param string $url 请求的url
     * @param array|null $options 请求选项
     * @param string|null $method 请求方式
     * @param array|null $headers 请求头
     * @param array|null $config GuzzleHttp\Client配置
     * @param array|null $poolHandlerOption 连接池(Hyperf\Guzzle\PoolHandler) 配置
     * @param array|array[]|null $handlerStackMiddlewares 重试中间件，默认：每隔 3000 milliseconds 重试一次，重试1次
     * @return array|null[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function httpRequest
    (
        string $url,
        ?array $options = [],
        ?string $method = 'POST',
        ?array $headers = [],
        ?array $config = [],
        ?array $poolHandlerOption = [],
        ?array $handlerStackMiddlewares = []
    ): array|null
    {

        $poolHandlerOption = Arr::collapse(
            [
                config('common.pool.http', []),
                $poolHandlerOption
            ]
        );

        $handlerStackMiddlewares = Arr::collapse(
            [
                config('common.handlerStackMiddlewares', ['retry' => null]),
                $handlerStackMiddlewares
            ]
        );

        $client = HttpClient::getClient($config, $poolHandlerOption, $handlerStackMiddlewares);

        /******************Request Options https://docs.guzzlephp.org/en/stable/request-options.html **********************************/
        $options = Arr::collapse(
            [
                [
                    //headers
                    RequestOptions::HEADERS => $headers,
                    //###http_errors  Set to false to disable throwing exceptions on an HTTP protocol errors (i.e., 4xx and 5xx responses). Exceptions are thrown by default when HTTP protocol errors are encountered.
                    //###Default:true
                    RequestOptions::HTTP_ERRORS => true,

                    //###ssl 正式验证  // Use the system's CA bundle (this is the default setting)
                    RequestOptions::VERIFY => false,// Disable validation entirely (don't do this!).

                    //###proxy (传递字符串以指定HTTP代理，或传递数组以指定用于不同协议的不同代理。) Pass a string to specify an HTTP proxy, or an array to specify different proxies for different protocols.
//                    RequestOptions::PROXY => [
////                        'http' => 'http://192.168.16.134:9501/', // Use this proxy with "http"
//                        'https' => 'http://bluelans:c4hpdJRTjLtG@47.91.246.112:14600/', // Use this proxy with "https",
////                        'no' => ['.mit.edu', 'httpbin.org']    // Don't use a proxy with these
//                    ],

//                    RequestOptions::ON_STATS => function (\GuzzleHttp\TransferStats $transferStats) use (&$responseData) {//请求回调方法
////                        var_dump($transferStats->getEffectiveUri()->__toString());
////                        var_dump($transferStats->getTransferTime());
////                        var_dump($transferStats->getHandlerStats());//curl_getinfo  (注意：stream=true 时 使用stream 发起请求 无法获取详细的响应数据)
//                        var_dump(\GuzzleHttp\Psr7\Message::toString($transferStats->getRequest()));
////                        $request = $transferStats->getRequest();
////                        $data = [
////                            'TransferTime' => $transferStats->getTransferTime(),//请求时间
////                            'transferStats' => $transferStats->getHandlerStats(),//curl_getinfo  (注意：stream=true 时 使用stream 发起请求 无法获取详细的响应数据)
////                            'requestMethod' => $request->getMethod(),//请求方式
////                            'requestUri' => $request->getUri(),//请求uri
////                            'requestProtocolVersion' => $request->getProtocolVersion(),//协议版本
////                            'requestHeaders' => $request->getHeaders(),//请求头
////                            'requestBody' => $request->getBody()->__toString(),//请求body
////                        ];
////                        var_dump($data);
////
//                        if ($transferStats->hasResponse()) {
//                            $response = $transferStats->getResponse();
//                            var_dump(\GuzzleHttp\Psr7\Message::toString($response));
////                            var_dump('hasResponse');
////                            var_dump($response->getStatusCode());
//
//                            $responseBody = $response->getBody()->getContents();
//                            $responseData = Arr::collapse([
//                                $responseData,
//                                [
//                                    Constant::RESPONSE_PROTOCOL_VERSION => $response->getProtocolVersion(),//协议版本
//                                    Constant::RESPONSE_STATUS_CODE => $response->getStatusCode(),//响应状态码 Constant::CODE_SUCCESS
//                                    Constant::RESPONSE_REASON_PHRASE => $response->getReasonPhrase(),//响应状态码描述 OK
//                                    Constant::RESPONSE_HEADERS => $response->getHeaders(),//响应头
//                                    Constant::RESPONSE_BODY => $responseBody,//响应body
//                                ]
//                            ]);
//                        } else {
//                            // Error data is handler specific. You will need to know what
//                            // type of error data your handler uses before using this
//                            // value.
////                            var_dump('!!!!!!!!!hasResponse');
////                            var_dump($transferStats->getHandlerErrorData());
//                        }
//
//                        $responseData[Constant::TRANSFER_TIME] = $transferStats->getTransferTime();
//                        $responseData[Constant::TRANSFER_STATS] = $transferStats->getHandlerStats();
//
//                    },

                ],
                $options
            ]);

        $responseData = [
            Constant::RESPONSE_PROTOCOL_VERSION => null,//协议版本
            Constant::RESPONSE_STATUS_CODE => null,//响应状态码 Constant::CODE_SUCCESS
            Constant::RESPONSE_REASON_PHRASE => null,//响应状态码描述 OK
            Constant::RESPONSE_HEADERS => null,//响应头
            Constant::RESPONSE_BODY => null,//响应body
            Constant::TRANSFER_TIME => null,//响应时间
            Constant::TRANSFER_STATS => null,//响应状态数据 curl_getinfo  (注意：stream=true 时 使用stream 发起请求 无法获取详细的响应数据)
            Constant::REQUEST_METHOD => $method,//请求方式
            Constant::REQUEST_URI => $url,//请求uri
//            Constant::REQUEST_HEADERS => $headers,//请求头
            Constant::REQUEST_BODY => $options,//请求body
        ];

        //返回上传文件的元数据
        foreach ($responseData as $key => $value) {

            if ($key != Constant::REQUEST_BODY) {
                continue;
            }

            foreach ($value as $_key => $_value) {
                if ($_key == RequestOptions::MULTIPART) {
                    if (MyArr::isIndexedArray($_value) && is_array(Arr::first($_value))) {
                        foreach ($_value as $index => $item) {
                            foreach ($item as $__key => $v) {
                                if (is_resource($v)) {
                                    $responseData[$key][$_key][$index][$__key] = stream_get_meta_data($v);
                                }
                            }
                        }
                    }
                }
            }
        }

        $response = null;
        try {
            $response = $client->request($method, $url, $options);
        } catch (RequestException $e) {
//            var_dump(\GuzzleHttp\Psr7\Message::toString($e->getRequest()));
            if ($e->hasResponse()) {
                $response = $e->getResponse();
//                var_dump(\GuzzleHttp\Psr7\Message::toString($response));
            } else {
                data_set($responseData, Constant::RESPONSE_STATUS_CODE, $e->getCode());
                data_set($responseData, Constant::RESPONSE_REASON_PHRASE, $e->getMessage());
            }
        } catch (Throwable $throwable) {
            go(function () use ($throwable, $responseData) {
                make(AppExceptionHandler::class)->log($throwable, 'warning', $responseData);
            });

            throw $throwable;
        }

        if ($response !== null) {
            $responseBody = $response->getBody()->getContents();
            $responseData = Arr::collapse([
                $responseData,
                [
                    Constant::RESPONSE_PROTOCOL_VERSION => $response->getProtocolVersion(),//协议版本
                    Constant::RESPONSE_STATUS_CODE => $response->getStatusCode(),//响应状态码 Constant::CODE_SUCCESS
                    Constant::RESPONSE_REASON_PHRASE => $response->getReasonPhrase(),//响应状态码描述 OK
                    Constant::RESPONSE_HEADERS => $response->getHeaders(),//响应头
                    Constant::RESPONSE_BODY => $responseBody,//响应body
                ]
            ]);
//            var_dump(\GuzzleHttp\Psr7\Message::toString($response));
        }

        return $responseData;
    }

}
