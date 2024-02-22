<?php

namespace Business\Hyperf\Utils\Cdn;

use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Utils\Context;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Exception\ParallelExecutionException;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Hyperf\Context\ApplicationContext;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\Stringable\Str;
use League\Flysystem\Config;
use League\Flysystem\Visibility;
use function Business\Hyperf\Utils\Collection\data_get;
use function Hyperf\Coroutine\parallel;

class AliOssCdn extends ResourcesCdn
{

    /**
     * 设置配置信息
     * @param int $storeId 商城id
     * @param array $configData 配置数据
     */
    public static function setConf($storeId = 1, $configData = [])
    {

        $key = static::getContextKey($storeId);

        return Context::storeData($key, function () use ($storeId, $configData) {
            //通过应用容器 获取配置类对象
            $config = getConfig();

            //获取 disk 名称
            $diskName = 'oss';
            if (empty($diskName)) {
                return [];
            }

            $configKey = 'file.storage.' . $diskName;

            //设置配置
            //获取默认配置
            $defaultDiskConf = $config->get('file.storage.oss');
            data_set($defaultDiskConf, 'diskName', $diskName);
//            'oss' => [
//                'driver' => \Hyperf\Filesystem\Adapter\AliyunOssAdapterFactory::class,
//                'accessId' => env('OSS_ACCESS_ID'),
//                'accessSecret' => env('OSS_ACCESS_SECRET'),
//                'bucket' => env('OSS_BUCKET'),
//                'endpoint' => env('OSS_ENDPOINT'),
//                // 'timeout' => 3600,
//                // 'connectTimeout' => 10,
//                // 'isCName' => false,
//                // 'token' => null,
//                // 'proxy' => null,
//            ],

            $config->set($configKey, $defaultDiskConf);

            return $config->get($configKey, $configData);
        });
    }

    /**
     * 获取云存储对象
     * @param array $extData
     * @return array  云存储对象
     */
    public static function getDisk($extData)
    {

        $rs = static::getDefaultResponseData(Constant::ORDER_STATUS_SHIPPED_INT, Constant::PARAMETER_STRING_DEFAULT);

        $storeId = data_get($extData, Constant::DB_COLUMN_SITE_ID, 0);
        if (empty($storeId)) {
            data_set($rs, Constant::CODE, 0);
            data_set($rs, Constant::MSG, 'store ID 异常');
            return $rs;
        }

        $configData = self::setConf($storeId);
        $diskName = data_get($configData, 'diskName');
        $driver = data_get($configData, 'driver');
        if (empty($diskName)) {
            data_set($rs, Constant::CODE, 2);
            data_set($rs, Constant::MSG, $storeId . ' oss 配置不存在');
            return $rs;
        }

        if (empty($driver)) {
            data_set($rs, Constant::CODE, 3);
            data_set($rs, Constant::MSG, $storeId . ' ' . $diskName . ' oss 云存储配置不存在');
            return $rs;
        }

        //获取文件系统对象
        $factory = ApplicationContext::getContainer()->get(FilesystemFactory::class);
        $filesystem = $factory->get($diskName);

        data_set($rs, Constant::DATA, $filesystem);
        data_set($rs, 'configData', $configData);

        return $rs;
    }

    /**
     * 获取资源域名
     * @param int $storeId 品牌id
     * @param int $resourceType 资源类型 0:所有 1:图片 2:视频 3:js 4:css 默认:1
     * @param array|null $domain cdn域名
     * @return array 图片cdn域名
     */
    public static function getResourceTypeDomain($storeId = 1, $resourceType = 0, $isCn = false, $domain = null)
    {
        $configData = static::setConf($storeId);
        $bucket = data_get($configData, 'bucket', '-1');

        $cdnDomains = data_get($configData, 'host.' . $bucket . '.' . $resourceType, []);

        if (is_array($domain)) {
            $cdnDomains = array_merge($cdnDomains, $domain);
        }

        return array_values(array_filter(array_unique($cdnDomains)));
    }

}
