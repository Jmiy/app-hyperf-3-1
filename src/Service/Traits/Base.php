<?php

/**
 * Base trait
 * User: Jmiy
 * Date: 2020-09-03
 * Time: 09:27
 */

namespace Business\Hyperf\Service\Traits;

use function Hyperf\Support\call;
use function Business\Hyperf\Utils\Collection\data_get;
use Hyperf\Collection\Arr;
use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Utils\Curl;
use Hyperf\HttpServer\Contract\RequestInterface;

trait Base
{

    /**
     * 获取类名
     * @return string
     */
    public static function getCustomClassName()
    {
        $class = explode('\\', get_called_class());
        $trans = array("Service" => "");
        return strtr(end($class), $trans);
    }

    /**
     * 获取当前调用的类名
     * @return string
     */
    public static function getCalledClassName()
    {
        $class = explode('\\', get_called_class());
        return end($class);
    }

    /**
     * 获取当前类的绝对路径
     * @return string
     */
    public static function getNamespaceClass()
    {
        return implode('', ['\\', static::class]);
    }

    /**
     * 获取服务提供者
     * @param string $platform 平台
     * @param string|array $serviceProvider 服务提供者
     * @return string 服务
     */
    public static function getServiceProvider(string $platform = '', string|array $serviceProvider = [])
    {
        $class = explode('\\', get_called_class());
        $trans = [
            '\\' . end($class) => ""
        ];

        $serviceData = Arr::collapse(
            [
                [strtr(static::getNamespaceClass(), $trans), $platform],
                (is_array($serviceProvider) ? $serviceProvider : [$serviceProvider])
            ]
        );

        return implode('\\', array_filter($serviceData));
    }

    /**
     * 执行服务
     * @param string $platform 平台
     * @param string|array $serviceProvider 服务提供者
     * @param string $method 执行方法
     * @param array|null $parameters 参数
     * @return mixed
     */
    public static function managerHandle(string $platform = '', string|array $serviceProvider = '', string $method = '', ?array $parameters = []): mixed
    {
        $service = static::getServiceProvider($platform, $serviceProvider);
//        if (!($service && $method && method_exists($service, $method))) {
//            return null;
//        }

        return call([$service, $method], $parameters);
    }

    /**
     * 包装数据
     * @param mixed $data 要包装的数据
     * @return false|string
     */
    public static function pack(mixed $data): string|false
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 解析数据
     * @param mixed $data 要解析的数据
     * @return mixed
     */
    public static function unpack(mixed $data): mixed
    {
        return json_decode($data, true);
    }

    /**
     * 强制转换为字符串
     * @param mix $value
     * @return string $value
     */
    public static function castToString($value)
    {
        return (string)$value;
    }

    /**
     * @param string $connection 数据库连接
     * @return mixed|null
     */
    public static function getServiceEnv($connection = 2)
    {
        $request = getApplicationContainer()->get(RequestInterface::class);
        $appEnv = $request->input('app_env', null); //开发环境 $request->route('app_env', null)
        return $appEnv === null ? $connection : ($appEnv . '_' . $connection);
    }

    /**
     * 构建批量数据
     * @param array $data 待处理的数据 如:['id'=>[[],[]...]]
     * @param int|null $limit 每批数据总数
     * @return array
     */
    public static function buildBatchData(array $data, ?int $limit = 50): array
    {
        $__data = [];//
        $countData = [];
        foreach ($data as $messageId => $_data) {
            $messageCount = count($_data);
            if ($messageCount >= $limit) {
                $__data[] = $_data;
                unset($data[$messageId]);
            } else {
                $countData[$messageId . '_index'] = $messageCount;
            }

        }

        // 从原始数组中提取值，并保存键
        $values = array_values($countData);

        // 使用 array_multisort() 函数对值进行排序
        // SORT_DESC 表示倒序排序
        array_multisort($values, SORT_DESC, $countData);

        $index = $__data ? count($__data) : 0;
        $_limit = 0;
        $__data[$index] = [];

        beginning:

        $i = count($countData);
        foreach ($countData as $_messageId => $__limit) {

            $messageId = Arr::first(explode('_', $_messageId));

            $i = $i - 1;
            if ($_limit + $__limit <= $limit) {
                $__data[$index] = Arr::collapse([$__data[$index], data_get($data, [$messageId], [])]);
                unset($data[$messageId]);
                unset($countData[$_messageId]);
                $_limit += $__limit;
            }

            if (($i <= 0 && count($data) > 0) || ($_limit == $limit)) {
                $_limit = 0;
                $index += 1;
                $__data[$index] = [];
                goto beginning;
            }
        }

        return $__data;
    }

}
