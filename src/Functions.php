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

use Hyperf\Support\Network;
use function Hyperf\Collection\data_set;
use function Hyperf\Support\make;
use function Business\Hyperf\Utils\Collection\data_get;
use Hyperf\Context\ApplicationContext;
use Hyperf\Collection\Arr;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Stringable\Str;
use Business\Hyperf\Utils\Arrays\MyArr;
use Business\Hyperf\Log\Loger;
use function Hyperf\Coroutine\go;
use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Job\PublicJob;
use Business\Hyperf\Service\BaseService;
use Business\Hyperf\Utils\Support\Facades\Queue;
use Carbon\Carbon;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Snowflake\IdGeneratorInterface;
use Hyperf\Context\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Psr\Log\LoggerInterface;

if (!function_exists('getApplicationContainer')) {
    /**
     * Return a Application Container.
     * @return ContainerInterface|null
     * @throws \TypeError
     */
    function getApplicationContainer(): ContainerInterface|null
    {

        //通过应用容器 获取配置类对象
        if (!ApplicationContext::hasContainer()) {
            throw new RuntimeException('The application context lacks the container.');
        }

        return ApplicationContext::getContainer();
    }
}

if (!function_exists('getConfigInterface')) {
    /**
     * Return a ConfigInterface.
     *
     * @return ConfigInterface
     */
    function getConfigInterface(): ConfigInterface
    {
        $container = getApplicationContainer();
        if (!$container->has(ConfigInterface::class)) {
            throw new RuntimeException('ConfigInterface is missing in container.');
        }
        return $container->get(ConfigInterface::class);
    }
}

if (!function_exists('getJobData')) {
    /**
     * 获取 job 执行配置数据
     * @param \Closure|object|string|func|mixed $callback
     * @param string $method
     * @param mixed $parameters
     * @param null|array $request
     * @param array $extData
     * @return array
     */
    function getJobData($callback, string $method = '', mixed $parameters = [], mixed $request = null, mixed $extData = [])
    {
        return Arr::collapse([
            [
                Constant::SERVICE => $callback,
                Constant::METHOD => $method,
                Constant::PARAMETERS => $parameters,
//                Constant::REQUEST_DATA => $request ?? (getApplicationContainer()->get(RequestInterface::class) ? getApplicationContainer()->get(RequestInterface::class)->all():[]),
                Constant::REQUEST_DATA => $request,
            ],
            $extData
        ]);
    }
}

if (!function_exists('pushQueue')) {
    /**
     * Push a new job onto the queue.
     *
     * @param string|object|array $job
     * @param mixed $data
     * @param string|null $channel 队列 channel
     * @return mixed
     */
    function pushQueue($job, $data = '', $channel = null)
    {
        $delay = data_get($job, Constant::QUEUE_DELAY, 0);//延迟时间 单位：秒
        $queueUnsetKeys = data_get($job, ['queueUnsetKeys']);//要清空的数据

        $connection = data_get($job, Constant::QUEUE_CONNECTION);
        $channel = $channel !== null ? $channel : data_get($job, Constant::QUEUE_CHANNEL);

        if (is_array($job)) {
            $waitingType = \Hyperf\Config\config('async_queue.' . $connection . '.waiting');
            if ($waitingType !== 'zset') {
                data_set($job, 'push_time', date('Y-m-d H:i:s'));
            }

            if ($queueUnsetKeys !== null) {
                foreach ($queueUnsetKeys as $queueUnsetKey) {
                    if (isset($job[$queueUnsetKey])) {
                        unset($job[$queueUnsetKey]);
                    }
                }
            }

            $data = [
                Constant::DATA => $job
            ];
            $job = PublicJob::class;
        }

        $retryPush = 0;
        pushBeginning:
        try {
            return Queue::push($job, $data, $delay, $connection, $channel);
        } catch (Throwable $exc) {

            if ($retryPush < 10) {
                $retryPush = $retryPush + 1;
                Coroutine::sleep(rand(3, 10));
                goto pushBeginning;
            }

            go(function () use ($exc) {
                throw $exc;
            });
        }

        return false;

    }
}

if (!function_exists('getInternalIp')) {
    /**
     * 获取服务器ip.
     * @return string|\RuntimeException
     */
    function getInternalIp(): string
    {
        //获取本服务的host
//        $host = config('services.rpc_service_provider.local.host', null);
//        if ($host !== null) {
//            return $host;
//        }

        return Network::ip();

//        $ips = swoole_get_local_ip();
//        if (is_array($ips) && !empty($ips)) {
//            return current($ips);
//        }
//        /** @var mixed|string $ip */
//        $ip = gethostbyname(gethostname());
//        if (is_string($ip)) {
//            return $ip;
//        }
//        throw new \RuntimeException('Can not get the internal IP.');
    }
}

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param string|null $make
     * @param array $parameters
     * @return mixed|\Psr\Container\ContainerInterface
     */
    function app($make = null, array $parameters = [])
    {
        if (is_null($make)) {
            return getApplicationContainer();
        }

        if (empty($parameters)) {
            $container = getApplicationContainer();
            //var_dump($make, $container->has($make));
            if ($container->has($make)) {
                return $container->get($make);
            }

            $config = $container->get(ConfigInterface::class);
            $_make = $config->get('dependencies.' . $make);
            //var_dump($_make, $container->has($_make));

            if ($container->has($_make)) {
                return $container->get($_make);
            }

            return make($_make, $parameters);
        }

        return make($make, $parameters);


    }
}

if (!function_exists('encrypt')) {
    /**
     * Encrypt the given value.
     *
     * @param string $value
     * @return string
     */
    function encrypt($value)
    {
        return app('encrypter')->encrypt($value);
    }
}

if (!function_exists('decrypt')) {
    /**
     * Decrypt the given value.
     *
     * @param string $value
     * @return string
     */
    function decrypt($value)
    {
        return app('encrypter')->decrypt($value);
    }
}

if (!function_exists('response')) {
    /**
     * Return a new response from the application.
     *
     * @param string $server
     * @param int $status
     * @param array $headers
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    function response($server = 'http_response', $status = Constant::CODE_SUCCESS, array $headers = [])
    {
        $response = app($server)->withStatus($status);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}

if (!function_exists('getConfig')) {
    /**
     * Return a ConfigInterface.
     *
     * @return ConfigInterface
     */
    function getConfig(): ConfigInterface
    {
        //通过应用容器 获取配置类对象
        return getConfigInterface();
    }
}

if (!function_exists('isValidIp')) {
    /**
     * Checks if the ip is valid.
     *
     * @param string $ip
     *
     * @return bool
     */
    function isValidIp($ip = null)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE)
        ) {
            return false;
        }

        return true;
    }
}

if (!function_exists('getClientIP')) {
    /**
     * Get the client IP address.
     *
     * @return client IP address
     */
    function getClientIP($ip = null)
    {
        if (!empty($ip)) {
            return $ip;
        }

        if (!Context::has('http.request.ipData')) {
            $remotes_keys = [
                'HTTP_X_FORWARDED_FOR',
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'x_forwarded_for',
                'client_ip',
                'x_forwarded',
                'forwarded_for',
                'forwarded',
                'addr',
                'x_cluster_client_ip',
                'x-forwarded-for',
                'client-ip',
                'x-forwarded',
                'forwarded-for',
                'remote-addr',
                'x-cluster-client-ip',
            ];

            $clientIP = '';
            $request = Context::get(ServerRequestInterface::class);
            $requestHeaders = $request->getHeaders();
            //var_dump(__METHOD__, $requestHeaders);
            foreach ($remotes_keys as $key) {
                $address = data_get($requestHeaders, strtolower($key));
                if (empty($address)) {
                    continue;
                }

                $address = is_array($address) ? $address : [$address];

                foreach ($address as $_address) {
                    $ipData = explode(',', $_address);
                    foreach ($ipData as $_clientIP) {
                        if (isValidIp($_clientIP)) {
                            return $_clientIP;
                        }
                    }
                }
            }

            if (empty($clientIP)) {
                $requestServer = $request->getServerParams();
                $address = data_get($requestServer, 'remote_addr');
                if ($address) {
                    $address = is_array($address) ? $address : [$address];

                    foreach ($address as $_address) {
                        $ipData = explode(',', $_address);
                        foreach ($ipData as $clientIP) {
                            if (isValidIp($clientIP)) {
                                return $clientIP;
                            }
                        }
                    }
                }
            }


            return Context::set('http.request.ipData', $clientIP);
        }

        return Context::get('http.request.ipData');

    }
}

if (!function_exists('getTranslator')) {
    /**
     * Return a Translator object.
     *
     * @return TranslatorInterface object
     */
    function getTranslator()
    {
        //通过应用容器 获取配置类对象
        return getApplicationContainer()->get(TranslatorInterface::class);
    }
}

if (!function_exists('getValidatorFactory')) {
    /**
     * Return a config object.
     *
     * @return config object
     */
    function getValidatorFactory()
    {
        //通过应用容器 获取配置类对象
        return app(ValidatorFactoryInterface::class);
    }
}

if (!function_exists('randomStr')) {
    /**
     * 随机数生成
     * @param int $length
     * @return string
     * @author Jmiy
     */
    function randomStr($length = 6)
    {

        $random = Str::random($length);

        return $random;
    }
}

if (!function_exists('getWhetherData')) {
    /**
     * 是否转化
     * @param string $whether
     * @return string
     */
    function getWhetherData($whether = '是')
    {
        $whetherData = [
            Constant::WHETHER_YES_VALUE => Constant::WHETHER_YES_VALUE_CN,
            Constant::WHETHER_NO_VALUE => Constant::WHETHER_NO_VALUE_CN,
            Constant::WHETHER_YES_VALUE_CN => Constant::WHETHER_YES_VALUE,
            Constant::WHETHER_NO_VALUE_CN => Constant::WHETHER_NO_VALUE,
        ];
        return data_get($whetherData, $whether, $whether);
    }
}

if (!function_exists('time2string')) {
    /**
     * 获取展示时间
     * @param int $second 秒
     * @return string
     */
    function time2string($second)
    {
        $day = floor($second / (3600 * 24));
        $second = abs($second % (3600 * 24));
        $hour = floor($second / 3600);
        $second = $second % 3600;
        $minute = floor($second / 60);
        $sec = $second % 60;
        return $day . '天' . $hour . '小时' . $minute . '分' . $sec . '秒';
    }
}

if (!function_exists('handleAccount')) {
    /**
     * 账号脱敏规则： 前面取三个字母，后面2个字母，中间全部隐藏，域名显示  muc***az@outlook.com   不够隐藏的就从第四个字母开始隐藏，域名都显示出来   所有品牌统一用这个规则
     * @param string $account
     * @return string
     */
    function handleAccount($account = '')
    {

        if (empty($account)) {
            return $account;
        }

        $start = strrpos($account, '@');

        $end = $start !== false ? ($start >= 3 ? 3 : $start) : (Str::length($account) >= 3 ? 3 : Str::length($account));

        $start = $start !== false ? $start : 0;

        for ($i = 2; $i >= 0; $i--) {
            if ($start - $i >= 0) {
                $start = $start - $i;
                break;
            }
        }

        return Str::substr($account, 0, $end) . '***' . Str::substr($account, $start);
    }
}

if (!function_exists('getTimeAt')) {
    /**
     * 时间转化
     * @return array
     */
    function getTimeAt($time)
    {

        if ($time === null) {
            return $time;
        }

        $timeData = [
            '不限' => null,
            'null' => '不限',
        ];

        return $time === 'all' ? $timeData : data_get($timeData, $time, $time);
    }
}

if (!function_exists('handleDatetime')) {
    /**
     * 处理时间数据
     * @param mix $data
     * @param string $format 时间格式
     * @param string $timezone 时区
     * @return mix 时间数据
     */
    function handleDatetime($data, $format = null, $attributes = null, $timezone = null)
    {

        $timeData = getTimeAt($data);

        $timeValue = strtotime($timeData);
        if ($timeValue !== false) {
            $timeData = $timeValue;
        }

        if (!is_numeric($timeData)) {
            return $timeData;
        }

        $timeData = Carbon::createFromTimestamp($timeData);
        if ($timezone !== null) {
            $timeData = $timeData->setTimezone($timezone);
        }

        if ($format !== null) {
            $timeData->format($format);
        }

        if ($attributes) {
            if (is_array($attributes)) {
                $_data = [];
                foreach ($attributes as $attribute) {
                    $_data[$attribute] = $attribute == 'date' ? $timeData->rawFormat($format) : data_get($timeData, $attribute, null);
                }

                return $_data;
            }

            return $attributes == 'date' ? $timeData->rawFormat($format) : data_get($timeData, $attributes, null);
        }

        return $timeData;
    }
}

if (!function_exists('handleNumber')) {
    /**
     * 处理数值
     * @param mix $value 要处理的数值
     * @param array $dateFormat 数据格式
     * @return mix 数值
     */
    function handleNumber($value, $dateFormat = [2, ".", ''])
    {
        $dateFormat = $dateFormat ? $dateFormat : [2, ".", ''];
        return number_format(floatval($value), ...$dateFormat);
    }
}

if (!function_exists('handleCollect')) {
    /**
     * 处理集合数据
     * @param array|collect $data 待处理的数据
     * @param string|array $type 类型
     * @param string $keyField key
     * @param string $valueField value
     * @return collect 集合数据
     */
    function handleCollect($data, $type = null, $keyField = null, $valueField = null)
    {

        $data = \Hyperf\Collection\collect($data);

        if ($keyField) {
            $keyField = '{key}';
        }

        if ($valueField) {
            $valueField = '{value}';
        }

        $data = $data->pluck($valueField, $keyField);

        if (!is_array($type)) {
            return $data;
        }

        $_data = [];
        $_keyData = [];
        foreach ($type as $typeValue) {
            foreach ($data as $key => $value) {
                if (Arr::accessible($value) || is_object($value)) {
                    if ($keyField && strpos(data_get($value, $keyField, ''), $typeValue) !== false) {
                        $keyData = explode(($typeValue . '_'), $key, 2);
                        $_key = $typeValue . '.' . data_get($keyData, 1, 0);
                        data_set($_data, $_key, $value);
                        unset($data[$key]);
                    } else {
                        if (data_get($value, 'type', '') == $typeValue) {
                            $currentKey = data_get($_keyData, $typeValue, 0);
                            $_key = $typeValue . '.' . $currentKey;
                            data_set($_data, $_key, $value);
                            data_set($_keyData, $typeValue, $currentKey + 1);
                        }
                    }
                } else {
                    $haystack = $key;
                    if ($keyField) {
                        $haystack = $key;
                    } else if ($valueField) {
                        $haystack = $value;
                    }
                    if (strpos($haystack, $typeValue) !== false) {
                        $keyData = explode(($typeValue . '_'), $haystack, 2);
                        $valueData = explode(($typeValue . '_'), $value, 2);

                        if ($keyField) {
                            $_key = $typeValue . '.' . data_get($keyData, 1, 0);
                            data_set($_data, $_key, data_get($valueData, 1, 0));
                        } else if ($valueField) {
                            $currentKey = data_get($_keyData, $typeValue, 0);
                            $_key = $typeValue . '.' . $currentKey;
                            data_set($_data, $_key, data_get($valueData, 1, 0));
                            data_set($_keyData, $typeValue, $currentKey + 1);
                        }
                        unset($data[$key]);
                    }
                }
            }
        }
        $data = \Hyperf\Collection\collect($_data);
        unset($_data);
        unset($_keyData);

        return $data;
    }
}

if (!function_exists('getCountry')) {
    /**
     * 获取用户国家
     * @param string $ip
     * @param string $country
     * @return string
     */
    function getCountry($ip = '', $country = '')
    {

        if ($country) {
            return $country;
        }

        $ip = $ip ? $ip : getClientIP();

        $ipIsValid = isValidIp($ip);
        $key = 'service';
        $geoipData = geoip()->setConfig($key, \Hyperf\Config\config('geoip.service'))->getLocation($ip)->toArray();
        $country = data_get($geoipData, 'iso_code', '');
        if (empty($country)) {

//            $exceptionName = '通过api获取ip国家失败：';
//            $messageData = ['ip:' . $ip, ' ip是否有效：' . ($ipIsValid ? '是' : '否')];
//            $message = implode(',', $messageData);
//            $parameters = [$exceptionName, $message, ''];
//            MonitorServiceManager::handle('Ali', 'Ding', 'report', $parameters);

            $value = 'maxmind_database';
            $geoipData = geoip()->setConfig($key, $value)->getLocation($ip)->toArray();
            $country = data_get($geoipData, 'iso_code', '');

//            if (empty($country)) {
//                $exceptionName = '通过maxmind_database获取ip国家失败：';
//                $parameters = [$exceptionName, $message, ''];
//                MonitorServiceManager::handle('Ali', 'Ding', 'report', $parameters);
//            }
        }

//        if (empty($country)) {//记录日志
//
//            $key = implode(':', ['log',__FUNCTION__, $ip]);
//            $ttl = BaseService::getTtl();
//            $handleCacheData = getJobData(BaseService::getNamespaceClass(), 'remember', [$key, $ttl, function () use($ip, $ipIsValid) {
//                $level = 'info';
//                $type = 'ip';
//                $subtype = 'country';
//                $keyinfo = $ip;
//                $content = [];
//                $subkeyinfo = $ipIsValid ? 1 : 0;
//                $extData = [];
//                $dataKey = null;
//                return BaseService::logs($level, $type, $subtype, $keyinfo, $content, $subkeyinfo, $extData, $dataKey);
//            }]);
//            BaseService::handleCache(BaseService::getCacheTags(), $handleCacheData);
//        }

        return $country ? $country : ($ipIsValid ? 'US' : '');
    }
}

if (!function_exists('setAppTimezone')) {
    /**
     * 设置时区
     * @param $appType
     * @param string $timezone
     * @param string $dbTimezone
     * @param null $appEnv
     * @return bool
     */
    function setAppTimezone($appType, $timezone = '', $dbTimezone = '', $appEnv = null)
    {
        if ($timezone) {
            date_default_timezone_set($timezone); //设置app时区 https://www.php.net/manual/en/timezones.php
        }

        return true;
    }
}

if (!function_exists('setLocale')) {
    /**
     * 设置国家语言
     * @param $country
     */
    function setLocale($country)
    {
        getTranslator()->setLocale($country);
    }
}

if (!function_exists('getExePlan')) {
    /**
     * 获取SQL执行计划
     * @param mix $star 星级
     * @return float $star 星级
     */
    function getExePlan(
        $connection,
        $table = null,
        $make = Constant::PARAMETER_STRING_DEFAULT,
        $from = Constant::PARAMETER_STRING_DEFAULT,
        $select = [],
        $where = [],
        $order = [],
        $limit = null,
        $offset = null,
        $isPage = false,
        $pagination = [],
        $isOnlyGetCount = false,
        $joinData = Constant::PARAMETER_ARRAY_DEFAULT,
        $with = [],
        $handleData = Constant::PARAMETER_ARRAY_DEFAULT,
        $unset = Constant::PARAMETER_ARRAY_DEFAULT,
        $relation = Constant::PARAMETER_STRING_DEFAULT,
        $setConnection = true,
        $default = Constant::PARAMETER_ARRAY_DEFAULT,
        $groupBy = Constant::PARAMETER_ARRAY_DEFAULT
    )
    {
        return [
            Constant::DB_EXECUTION_PLAN_SETCONNECTION => $setConnection,
            Constant::CONNECTION => $connection,
            Constant::DB_EXECUTION_PLAN_TABLE => $table,
            Constant::DB_EXECUTION_PLAN_MAKE => $make,
            Constant::DB_EXECUTION_PLAN_FROM => $from,
            Constant::DB_EXECUTION_PLAN_SELECT => $select,
            Constant::DB_EXECUTION_PLAN_WHERE => $where,
            Constant::DB_EXECUTION_PLAN_ORDERS => $order,
            Constant::DB_EXECUTION_PLAN_LIMIT => $limit,
            Constant::DB_EXECUTION_PLAN_OFFSET => $offset,
            Constant::DB_EXECUTION_PLAN_IS_PAGE => $isPage,
            Constant::DB_EXECUTION_PLAN_PAGINATION => $pagination,
            Constant::DB_EXECUTION_PLAN_IS_ONLY_GET_COUNT => $isOnlyGetCount,
            Constant::DB_EXECUTION_PLAN_JOIN_DATA => $joinData,
            Constant::DB_EXECUTION_PLAN_WITH => $with,
            Constant::DB_EXECUTION_PLAN_HANDLE_DATA => $handleData,
            Constant::DB_EXECUTION_PLAN_UNSET => $unset,
            Constant::DB_EXECUTION_PLAN_RELATION => $relation,
            Constant::DB_EXECUTION_PLAN_DEFAULT => $default,
            Constant::DB_EXECUTION_PLAN_GROUPBY => $groupBy,
        ];
    }
}

if (!function_exists('getExePlanJoinData')) {
    /**
     * 获取SQL执行计划 表关联
     * @param string $table 表
     * @param string|function $first
     * @param string|null $operator
     * @param string|null $second
     * @param string $type
     * @return array SQL执行计划 表关联
     */
    function getExePlanJoinData($table, $first, $operator = null, $second = null, $type = 'left')
    {
        return [
            Constant::DB_EXECUTION_PLAN_TABLE => $table,
            Constant::DB_EXECUTION_PLAN_FIRST => $first,
            Constant::DB_COLUMN_OPERATOR => $operator,
            Constant::DB_EXECUTION_PLAN_SECOND => $second,
            Constant::DB_COLUMN_TYPE => $type,
        ];
    }
}

if (!function_exists('getExePlanHandleData')) {
    /**
     * 获取SQL执行计划 数据处理结构数据
     * @param string $field 字段名
     * @param mix $default 默认值
     * @param array $data 数据映射map
     * @param string $dataType 数据类型
     * @param string $dateFormat 数据格式
     * @param string $time 时间处理句柄
     * @param string $glue 分隔符或者连接符
     * @param boolean $isAllowEmpty 是否允许为空 true：是  false：否
     * @param array $callback 回调闭包数组
     * @param array $only 返回字段
     * @return array 数据处理结构数据
     */
    function getExePlanHandleData($field = null, $default = Constant::PARAMETER_STRING_DEFAULT, $data = Constant::PARAMETER_ARRAY_DEFAULT, $dataType = Constant::PARAMETER_STRING_DEFAULT, $dateFormat = Constant::PARAMETER_STRING_DEFAULT, $time = Constant::PARAMETER_STRING_DEFAULT, $glue = Constant::PARAMETER_STRING_DEFAULT, $isAllowEmpty = true, $callback = Constant::PARAMETER_ARRAY_DEFAULT, $only = Constant::PARAMETER_ARRAY_DEFAULT)
    {
        return [
            Constant::DB_EXECUTION_PLAN_FIELD => $field, //数据字段
            Constant::DATA => $data, //数据映射map
            Constant::DB_EXECUTION_PLAN_DATATYPE => $dataType, //数据类型
            Constant::DB_EXECUTION_PLAN_DATA_FORMAT => $dateFormat, //数据格式
            Constant::DB_EXECUTION_PLAN_TIME => $time, //时间处理句柄
            Constant::DB_EXECUTION_PLAN_GLUE => $glue, //分隔符或者连接符
            Constant::DB_EXECUTION_PLAN_IS_ALLOW_EMPTY => $isAllowEmpty, //是否允许为空 true：是  false：否
            Constant::DB_EXECUTION_PLAN_DEFAULT => $default, //默认值$default
            Constant::DB_EXECUTION_PLAN_CALLBACK => $callback,
            Constant::DB_EXECUTION_PLAN_ONLY => $only,
        ];
    }
}

if (!function_exists('handleTime')) {
    /**
     * 处理时间
     * @param string|max $dataTime 时间数据
     * @param string $time
     * @param string $dateFormat 时间格式 默认：Y-m-d H:i:s
     * @return string 时间
     */
    function handleTime($dataTime, $time = '', $dateFormat = 'Y-m-d H:i:s')
    {

        $timeValue = strtotime($dataTime);

        if (!($timeValue !== false && $dataTime != '0000-00-00 00:00:00')) {
            return $dataTime;
        }

        if (is_string($dataTime)) {
            $value = Carbon::parse($dataTime)->rawFormat($dateFormat);
        } else {
            $value = Carbon::createFromTimestamp($dataTime)->rawFormat($dateFormat);
        }

        if ($time) {
            $time = strtotime($time, strtotime($value));
            $value = Carbon::createFromTimestamp($time)->rawFormat($dateFormat);
        }

        return $value;
    }
}

if (!function_exists('handleData')) {
    /**
     * 处理数据
     * @param array|obj $value
     * @param string|array $field [
     * 'field' => 'interests.*.interest',//数据字段
     * Constant::DATA => [],//数据映射map
     * 'dataType' => 'string',//数据类型
     * 'dateFormat' => 'Y-m-d H:i:s',//数据格式
     * 'time' => '+1year',//时间处理句柄
     * 'glue' => ',',//分隔符或者连接符
     * 'is_allow_empty' => true,//是否允许为空 true：是  false：否
     * 'default' => '',//默认值$default
     * 'only' => [],
     * 'callback' => [
     * "amount" => function($item) {
     * return data_get($item, 'item_price_amount', 0) - data_get($item, 'promotion_discount_amount', 0);
     * },
     * ],
     * ]
     * @return string|array
     */
    function handleData($value, $field)
    {

        $fieldData = []; //数据映射map
        $dataType = ''; //数据类型
        $glue = ','; //分隔符或者连接符
        $default = ''; //默认值$default
        $dateFormat = 'Y-m-d H:i:s'; //数据格式
        $time = ''; //时间处理句柄
        $isAllowEmpty = true; //是否允许为空 true：是  false：否
        $only = []; //只要 only 里面的字段
        $callback = null; //回调
        $srcFiel = $field;
        if (is_array($field)) {
            $fieldData = data_get($field, Constant::DATA, []);
            $dataType = data_get($field, 'dataType', $dataType);
            $dateFormat = data_get($field, 'dateFormat', $dateFormat);
            $glue = data_get($field, 'glue', $glue);
            $default = data_get($field, 'default', $default);
            $time = data_get($field, 'time', $time);
            $isAllowEmpty = data_get($field, 'is_allow_empty', $isAllowEmpty);
            $only = data_get($field, 'only', $only); //只要 only 里面的字段
            $callback = data_get($field, 'callback', $callback); //回调
            $field = data_get($field, 'field', $field);
        }

        if (strpos($field, '{or}') !== false) {
            $_fieldData = explode('{or}', $field);
            $_value = $default;
            foreach ($_fieldData as $orField) {
                $_field = [
                    'field' => $orField,
                    Constant::DATA => $fieldData,
                    'dataType' => $dataType,
                    'dateFormat' => $dateFormat,
                    'glue' => $glue,
                    'default' => $default,
                ];

                $_value = handleData($value, $_field);
                if ($_value) {
                    break;
                }
            }
            $value = $_value;
        } else if (strpos($field, '{connection}') !== false) {
            $_fieldData = explode('{connection}', $field);
            $_value = [];
            foreach ($_fieldData as $connectionField) {
                $_field = [
                    'field' => $connectionField,
                    Constant::DATA => $fieldData,
                    'dataType' => $dataType,
                    'dateFormat' => $dateFormat,
                    'glue' => $glue,
                    'default' => $default,
                ];
                $_value[] = handleData($value, $_field);
            }
            $value = $_value;
        } else if (strpos($field, '|') !== false) {

            $segments = explode('.', $field);
            $field = [];
            foreach ($segments as $segment) {

                if (strpos($segment, '|') === false) {
                    $field[] = $segment;
                    continue;
                }

                if ($field) {
                    $field = implode('.', $field);
                    $value = data_get($value, $field, $default);
                    $field = [];
                }

                $_segments = explode('|', $segment);
                $nextSegment = '';
                foreach ($_segments as $_key => $_segment) {

                    if ($nextSegment == $_segment) {
                        continue;
                    }

                    switch ($_segment) {
                        case 'json':
                            $nextSegment = data_get($_segments, $_key + 1, '');
                            $value = data_get($value, $nextSegment, $default);
                            $value = Arr::accessible($value) ? $value : json_decode($value, true);
                            $value = Arr::accessible($value) ? $value : $default;
                            break;

                        default:
                            $value = data_get($value, $_segment, $default);
                            break;
                    }
                }
            }

            if ($field) {
                $field = implode('.', $field);
                $value = data_get($value, $field, $default);
            }
        } else {
            $value = data_get($value, $field, $default);
        }

        if (!$isAllowEmpty && empty($value)) {//如果不允许为空并且当前值为空，就使用默认值$default
            $value = $default;
        }

        if ($fieldData) {
            $value = $value === null ? '' : $value;
            $value = data_get($fieldData, $value, $default);
        }

        if ($callback) {
            foreach ($callback as $key => $func) {
                if (Arr::accessible($value) && !Arr::isAssoc($value)) {//如果是 索引数组，就进行递归处理
                    foreach ($value as $_key => $item) {
                        if (Arr::isAssoc($item)) {
                            data_set($value, $_key, handleData($item, $srcFiel));
                        }
                    }
                } else {
                    if (false === strpos($key, '{nokey}')) {
                        data_set($value, $key, $func($value));
                    } else {
                        $func($value);
                    }
                }
            }
        }

        if ($only) {
            if (Arr::accessible($value) && !Arr::isAssoc($value)) {
                foreach ($value as $key => $item) {
                    $srcFiel['field'] = null;
                    data_set($value, $key, handleData($item, $srcFiel));
                }
            } else {
                $value = Arr::only($value, $only);
            }
        }


//        var_dump($fieldData);
//        var_dump($value);
//        exit;
//        if (strpos($field, '{or}') !== false) {
//            dd($field, $value);
//        }

        switch ($dataType) {
            case 'string':
                if (Arr::accessible($value)) {

                    if (!is_array($value)) {
                        $value = $value->toArray();
                    }

                    if (is_array($value)) {
                        $value = array_unique(array_filter($value));
                        $value = implode($glue, $value);
                    }
                }
                $value = $value . '';

                break;

            case 'array':
                $value = is_array($value) ? $value : explode($glue, $value);
                $value = array_filter(array_unique($value));
                break;

            case 'datetime':

                $value = handleTime($value, $time, $dateFormat);
                if ($value === '0000-00-00 00:00:00') {
                    $value = '';
                }
                break;

            case 'int':
                $value = intval($value);
                break;

            case 'price':
                $dateFormat = $dateFormat ? $dateFormat : [2, ".", ''];
                $value = number_format(floatval($value), ...$dateFormat);
                break;

            default:
                break;
        }

        return $value;
    }
}

if (!function_exists('handleResponseData')) {
    /**
     * 处理响应数据
     * @param \Hypert\Database\Model\Collection $data obj $data 数据句柄
     * @param array $dbExecutionPlan sql执行计划
     * @param boolean $flatten 是否将数据平铺  true：是  false：否
     * @param boolean $isGetQuery 是否获取查询句柄Query true：是  false:否
     * @param string $dataStructure 数据结构
     * @return array  响应数据
     */
    function handleResponseData($data = null, &$dbExecutionPlan = [], $flatten = false, $isGetQuery = false, $dataStructure = 'one')
    {

        if ($data->isEmpty()) {
            return [];
        }

        $allData = $data->toArray();

        $parentData = data_get($dbExecutionPlan, Constant::DB_EXECUTION_PLAN_PARENT, []);
        $with = data_get($dbExecutionPlan, 'with', []);
        $itemHandleData = data_get($dbExecutionPlan, Constant::DB_EXECUTION_PLAN_ITEM_HANDLE_DATA, []); //数据行整体处理
        foreach ($allData as $index => $data) {
            $forgetKeys = [];

            $handleData = data_get($parentData, 'handleData', []);
            if ($handleData) {
                foreach ($handleData as $key => $field) {
                    data_set($data, $key, handleData($data, $field));
                }
            }

            $unset = data_get($parentData, 'unset', []);
            if ($unset) {
                $forgetKeys = Arr::collapse([$forgetKeys, $unset]);
            }

            foreach ($with as $relationKey => $relationData) {

                $relation = data_get($relationData, 'relation', '');
                $relationDbDefaultData = data_get($relationData, 'default', []);

                $relationDbData = data_get($data, $relationKey, []);
                $handleData = data_get($relationData, 'handleData', []);
                if (empty($relationDbData) && $relation == 'hasOne') {//如果关系数据为空，就设置默认值
                    $select = data_get($with, $relationKey . '.select', []);
                    foreach ($select as $key) {

                        if (stripos($key, ' as ') !== false) {
                            $segments = preg_split('/\s+as\s+/i', $key);
                            $key = end($segments) ? end($segments) : $key;
                        }

                        $arrIndex = $relationKey . '.' . $key;
                        data_set($data, $arrIndex, data_get($data, data_get($relationDbDefaultData, $key, ''), (isset($handleData[$arrIndex]['default']) ? $handleData[$arrIndex]['default'] : '')));
                    }
                }

                if ($handleData) {
                    if ($relation && $relation != 'hasOne') {
                        foreach ($relationDbData as $_index => $item) {
                            foreach ($handleData as $key => $field) {
                                data_set($data, $relationKey . '.' . $_index . '.' . $key, handleData($item, $field));
                            }
                        }
                    } else {
                        foreach ($handleData as $key => $field) {
                            data_set($data, $key, handleData($data, $field));
                        }
                    }
                }

                $unset = data_get($relationData, 'unset', []);
                if ($unset) {
                    $forgetKeys = Arr::collapse([$forgetKeys, $unset]);
                }
            }

            if ($itemHandleData) {
                $data = handleData($data, $itemHandleData);
            }

            if ($flatten) {
                $data = MyArr::flatten($data);
            }

            if ($forgetKeys) {
                Arr::forget($data, $forgetKeys);
            }

            $allData[$index] = $data;
        }

        $dataStructure = strtolower($dataStructure);
        switch ($dataStructure) {
            case 'one':
                $data = Arr::first($allData);
                break;

            default:
                $data = $allData;
                break;
        }

        return $data;
    }
}

if (!function_exists('handleRelation')) {
    /**
     * 获取响应数据
     * @return mix 当前路由uri
     */
    function handleRelation($data = null, &$dbExecutionPlan = [])
    {
        $with = data_get($dbExecutionPlan, 'with', []);
        if (empty($with)) {
            return $data;
        }

        foreach ($with as $relationKey => $relationData) {
            $data = $data->with([$relationKey => function ($relation) use ($relationData, $relationKey, &$dbExecutionPlan) {

                $setConnection = data_get($relationData, 'setConnection', false);
                $storeId = data_get($relationData, 'storeId', 0);
                if ($setConnection) {
                    BaseService::createModel($storeId, null, [], '', $relation); //设置关联对象relation 数据库连接
                }

                $morphToConnection = data_get($relationData, 'morphToConnection', []);
                if ($morphToConnection) {
                    $relation->getModel()->setMorphToConnection($morphToConnection);
                }

                $where = data_get($relationData, 'where', []);
                if ($where) {
                    $relation->buildWhere($where);
                }

                $select = data_get($relationData, 'select', []);
                if ($select) {
                    $relation->select($select);
                }

                $groupBy = data_get($relationData, Constant::DB_EXECUTION_PLAN_GROUPBY, '');
                if ($groupBy) {
                    $relation = $relation->groupBy($groupBy);
                }

                $orders = data_get($relationData, 'orders', []);
                if ($orders) {
                    foreach ($orders as $order) {
                        $relation->orderBy($order[0], $order[1]);
                    }
                }

                $offset = data_get($relationData, 'offset', null);
                if ($offset !== null) {
                    $relation->offset($offset);
                }

                $limit = data_get($relationData, 'limit', null);
                if ($limit !== null) {
                    $relation->limit($limit);
                }

                handleRelation($relation, $relationData);
            }
            ]);
        }
        return $data;
    }
}

if (!function_exists('handleQuery')) {
    /**
     * 获取响应数据
     * @param obj $builder 数据库操作句柄
     * @param array $dbExecutionPlan sql执行计划
     * @param boolean $flatten 是否将数据平铺  true：是  false：否
     * @param boolean $isGetQuery 是否获取查询句柄Query true：是  false:否
     * @param string $dataStructure 数据结构
     * @return obj|array  响应数据
     */
    function handleQuery($builder = null, &$dbExecutionPlan = [], $flatten = false, $isGetQuery = false, $dataStructure = 'one')
    {

        $parentData = data_get($dbExecutionPlan, Constant::DB_EXECUTION_PLAN_PARENT, []);

        $countBuilder = null;
        $isPage = data_get($parentData, 'isPage', false); //是否获取分页
        $isOnlyGetCount = data_get($parentData, 'isOnlyGetCount', false); //是否只要分页数据
        $pagination = data_get($parentData, Constant::DB_EXECUTION_PLAN_PAGINATION, []); //分页数据
        if (empty($builder)) {
            if (empty($parentData)) {
                return $builder;
            }

            $countBuilder = $builder ? (clone $builder) : null;
            if (empty($builder)) {
                $make = data_get($parentData, Constant::DB_EXECUTION_PLAN_MAKE, '');
                if (empty($make)) {
                    return $builder;
                }

                $connection = data_get($parentData, Constant::CONNECTION, Constant::DB_CONNECTION_DEFAULT);
                $parameters = data_get($parentData, Constant::PARAMETERS, []);
                $table = data_get($parentData, Constant::DB_EXECUTION_PLAN_TABLE);

                if (false !== strpos($make, '\\App\\Service\\')) {
                    $builder = $make::getModel($connection, $table, $parameters);
                } else {
                    $builder = BaseService::createModel($connection, $make, $parameters, $table);
                }

                $from = data_get($parentData, Constant::DB_EXECUTION_PLAN_FROM, '');
                if ($from) {
                    $builder = $builder->from($from);
                }


                $joinData = data_get($parentData, Constant::DB_EXECUTION_PLAN_JOIN_DATA);
                if ($joinData) {
                    foreach ($joinData as $joinItem) {
                        $table = data_get($joinItem, Constant::DB_EXECUTION_PLAN_TABLE, '');
                        $first = data_get($joinItem, Constant::DB_EXECUTION_PLAN_FIRST, '');
                        $operator = data_get($joinItem, Constant::DB_COLUMN_OPERATOR, null);
                        $second = data_get($joinItem, Constant::DB_EXECUTION_PLAN_SECOND, null);
                        $type = data_get($joinItem, Constant::DB_COLUMN_TYPE, 'inner');
                        $where = data_get($joinItem, Constant::DB_EXECUTION_PLAN_WHERE, false);
                        $builder = $builder->join($table, $first, $operator, $second, $type, $where);
                    }
                }

                $where = data_get($parentData, 'where', []);
                if ($where) {
                    $builder = $builder->buildWhere($where);
                }

                if ($isPage || $isOnlyGetCount) {
                    $countBuilder = clone $builder;
                }

                $select = data_get($parentData, 'select', []);
                if ($select) {
                    $builder = $builder->select($select);
                }

                $groupBy = data_get($parentData, Constant::DB_EXECUTION_PLAN_GROUPBY, '');
                if ($groupBy) {
                    $builder = $builder->groupBy($groupBy);
                }

                $orders = data_get($parentData, 'orders', []);
                if ($orders) {
                    $orders = is_array($orders) ? $orders : [$orders];
                    foreach ($orders as $order) {

                        if (empty($order)) {
                            continue;
                        }

                        if (is_string($order)) {
                            $builder = $builder->orderByRaw($order);
                        } else if (is_array($order)) {
                            $column = data_get($order, 0, '');
                            $direction = data_get($order, 1, 'asc');
                            if ($column) {
                                $builder = $builder->orderBy($column, $direction);
                            }
                        }
                    }
                }

                $offset = data_get($parentData, 'offset', null);
                if ($offset !== null) {
                    $builder = $builder->offset($offset);
                }

                $limit = data_get($parentData, 'limit', null);
                if ($limit !== null) {
                    $builder = $builder->limit($limit);
                }
            }
        }

        $count = true;
        if (!$isGetQuery && $countBuilder && ($isPage || $isOnlyGetCount)) {
            $limit = data_get($pagination, Constant::PAGE_SIZE, 10);
            $count = $countBuilder->count();
            data_set($pagination, Constant::TOTAL, $count);
            data_set($pagination, Constant::TOTAL_PAGE, ceil($count / $limit));
        }

        if ($isOnlyGetCount) {
            return $pagination;
        }

        if (empty($count)) {
            return $isPage ? [Constant::DATA => [], Constant::DB_EXECUTION_PLAN_PAGINATION => $pagination] : [];
        }

        handleRelation($builder, $dbExecutionPlan);

        if ($isGetQuery) {
            return $isPage ? ['countBuilder' => $countBuilder, 'builder' => $builder] : $builder;
        }

        $data = $builder->get();

        $data = handleResponseData($data, $dbExecutionPlan, $flatten, $isGetQuery, $dataStructure);

        return $isPage ? [Constant::DATA => $data, Constant::DB_EXECUTION_PLAN_PAGINATION => $pagination,] : $data;
    }
}

if (!function_exists('getDbBeforeHandle')) {
    /**
     * 获取数据库操作前要完成的 handle
     * @param array $updateHandle
     * @param array $deleteHandle
     * @param array $insertHandle
     * @param null $selectHandle
     * @return array
     */
    function getDbBeforeHandle($updateHandle = [], $deleteHandle = [], $insertHandle = [], $selectHandle = null)
    {
        return [
            Constant::DB_OPERATION_UPDATE => $updateHandle,
            Constant::DB_OPERATION_DELETE => $deleteHandle,
            Constant::DB_OPERATION_INSERT => $insertHandle,
            Constant::DB_OPERATION_SELECT => $selectHandle,
        ];
    }
}

if (!function_exists('getUniqueId')) {
    /**
     * @return mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    function getUniqueId(): mixed
    {
        $retry = 0;
        beginning:
        try {
            return ApplicationContext::getContainer()->get(IdGeneratorInterface::class)->generate();
        } catch (Throwable $throwable) {

            if ($retry < 3) {
                $retry = $retry + 1;
                Coroutine::sleep(rand(1, 5));
                goto beginning;
            }

            throw $throwable;
        }

    }
}

if (!function_exists('loger')) {
    /**
     * @param string $name Channel 的名字
     * @param string|null $group config/autoload/logger.php 配置文件中的log处理器 key 默认：default
     * @return \Psr\Log\LoggerInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    function loger(string $name = 'app', ?string $group = 'default'): LoggerInterface
    {
        return Loger::get($name, $group);
    }
}

if (!function_exists('arrayTrim')) {
    function arrayTrim(string|array $input): string|array
    {
        if (!is_array($input)) {
            return trim($input);
        }
        return array_map('arrayTrim', $input);
    }
}

if (!function_exists('removeUtf8Bom')) {
    function removeUtf8Bom(string $text): string
    {
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        return $text;
    }
}

if (!function_exists('decodeSku')) {
    /**
     * @param $sku
     * @return array
     * sku解码
     */
    function decodeSku($sku)
    {
        if (strpos($sku, 'UGT-') !== false) {
            $sku = str_replace('UGT-', 'AGT-', $sku);
        }
        //如果匹配规则
        if (preg_match('/^([C|c][0-9]+)/i', $sku)) {
            preg_match('/^([C|c][0-9]+)/i', $sku, $match);
            $available_sku = !empty($match[1]) ? $match[1] : '';
            if (strtoupper(substr($available_sku, 0, 1)) == "C") {
                $sku_product_id = substr($available_sku, 1);
                $sku_product_code = strtoupper(substr($available_sku, 0, 1));
            }
        } else if (
            preg_match('/^[U|u]?(\d{6,7})$/i', $sku)
            || preg_match('/^[U|u]?(\d{6,7})[^\d]+/i', $sku)
        ) {
            preg_match('/^[U|u]?(\d{6,7})/i', $sku, $match);
            $products_unique_number = !empty($match[1]) ? $match[1] : '';
            $sku_product_id = $products_unique_number;
            $sku_product_code = "U";
        } else if (preg_match('/^[A|a]?(\S{6,15})$/i', $sku)) {
            preg_match('/^[A|a]?(\S{6,15})$/i', $sku, $match);
            $products_unique_number = !empty($match[1]) ? $match[1] : '';
            $sku_product_id = $products_unique_number;
            $sku_product_code = "A";
        } else {
            $sku_product_id = 0;
            $sku_product_code = '';
        }

        return array('sku' => $sku_product_id, 'code' => $sku_product_code);
    }

}

if (!function_exists('getScheduleConf')) {
    /**
     * 获取调度配置
     * @param $second
     * @param $lifecycle
     * @param null $sec
     * @param null $minute
     * @param null $hour
     * @param null $day
     * @param null $month
     * @return string
     */
    function getScheduleConf($second, $lifecycle, $sec = null, $minute = null, $hour = null, $day = null, $month = null)
    {
        $second = $second % $lifecycle;

        $month = $month === null ? '*' : $month;

        $separator = '';
        $_separator = '/';
        $__separator = '-';
        $_day = floor($second / (86400));
        if ($day === null) {
            $day = $_day > 0 ? ($_day < 10 ? ('0' . $_day) : $_day) : '*';
        } else if (false !== stripos($day, $_separator)) {
            $separator = $_separator;
        } else if (false !== stripos($day, $__separator)) {
            $separator = $__separator;
        }
        if ($separator) {
            $data = explode($separator, $day);
            $start = $data[0];
            $end = $data[1];

            if ($start === 'null') {
                $start = $_day;
            }

            $day = implode($separator, [$start, $end]);
        }

        $separator = '';
        $second = abs($second % (86400));
        $_start = floor($second / 3600);
        if ($hour === null) {
            $hour = $_start < 10 ? ('0' . $_start) : $_start;
        } else if (false !== stripos($hour, $_separator)) {
            $separator = $_separator;
        } else if (false !== stripos($hour, $__separator)) {
            $separator = $__separator;
        }
        if ($separator) {
            $data = explode($separator, $hour);
            $start = $data[0];
            $end = $data[1];

            if ($start === 'null') {
                $start = $_start;
            }

            $hour = implode($separator, [$start, $end]);
        }

        $second = $second % 3600;
        $separator = '';
        $_start = floor($second / 60);
        if ($minute === null) {
            $minute = $_start < 10 ? ('0' . $_start) : $_start;
        } else if (false !== stripos($minute, $_separator)) {
            $separator = $_separator;
        } else if (false !== stripos($minute, $__separator)) {
            $separator = $__separator;
        }
        if ($separator) {
            $data = explode($separator, $minute);
            $start = $data[0];
            $end = $data[1];

            if ($start === 'null') {
                $start = $_start;
            }

            $minute = implode($separator, [$start, $end]);
        }

        $separator = '';
        $_start = $second % 60;
        if ($sec === null) {
            $sec = $_start < 10 ? ('0' . $_start) : $_start;
        } else if (false !== stripos($sec, $_separator)) {
            $separator = $_separator;
        } else if (false !== stripos($sec, $__separator)) {
            $separator = $__separator;
        }
        if ($separator) {
            $data = explode($separator, $sec);
            $start = $data[0];
            $end = $data[1];

            if ($start === 'null') {
                $start = $_start;
            }

            $sec = implode($separator, [$start, $end]);
        }

        return sprintf(
            '%s %s %s %s %s ?',
            $sec,
            $minute,
            $hour,
            $day,
            $month
        );
    }

}



