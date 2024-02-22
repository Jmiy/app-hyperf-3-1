<?php

namespace Business\Hyperf\Utils\Cdn;

use Hyperf\Context\ApplicationContext;
use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Utils\Context;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\Utils\Str;
use League\Flysystem\Visibility;
use League\Flysystem\Config;

class AwsS3Cdn extends ResourcesCdn
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
            $diskName = 's3';
            if (empty($diskName)) {
                return [];
            }

            $configKey = 'file.storage.' . $diskName;

//            $configData = $config->get($configKey, $configData);
//            if(!empty($configData)){
//                return $configData;
//            }

//            //获取driver配置
//            $diskConf = DictStoreService::getListByType($storeId, $diskName);
//            if ($diskConf->isEmpty()) {
//                return [
//                    'diskName' => $diskName,
//                ];
//            }

            //设置配置
            //获取默认配置
            $defaultDiskConf = $config->get('file.storage.s3');
            data_set($defaultDiskConf, 'diskName', $diskName);
//            [
//                'driver' => \Hyperf\Filesystem\Adapter\S3AdapterFactory::class,
//                'credentials' => [
//                    'key' => env('S3_KEY'),
//                    'secret' => env('S3_SECRET'),
//                ],
//                'region' => env('S3_REGION'),
//                'version' => 'latest',
//                'bucket_endpoint' => false,
//                'use_path_style_endpoint' => false,
//                'endpoint' => env('S3_ENDPOINT'),
//                'bucket_name' => env('S3_BUCKET'),
//                'host' => [
//                    'bucket_name' => [
//                        1 => ['http://us-cube-img.stosz.com'], // 图片域名
//                        2 => ['http://us-cube-video.stosz.com'], //视频域名
//                        3 => ['http://us-cube-js-css.stosz.com'], // js域名
//                        4 => ['http://us-cube-js-css.stosz.com'], //css域名
//                    ]
//                ],
//            ];

            //更新配置
//            foreach ($diskConf as $item) {
//                $key = data_get($item, 'conf_key');
//
//                if($key == 'driver'){
//                    continue;
//                }
//
//                switch ($key) {
//                    case 'key':
//                    case 'secret':
//                        $key = 'credentials.'.$key;
//
//                        break;
//
//                    case 'bucket':
//                        $key = 'bucket_name';
//
//                        break;
//
//                    default:
//                        break;
//                }
//
//                $value = data_get($item, 'conf_value', data_get($defaultDiskConf, $key, ''));
//                data_set($defaultDiskConf, $key, $value);
//            }

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
            data_set($rs, Constant::MSG, $storeId . ' s3 配置不存在');
            return $rs;
        }

        if (empty($driver)) {
            data_set($rs, Constant::CODE, 3);
            data_set($rs, Constant::MSG, $storeId . ' ' . $diskName . ' s3 云存储配置不存在');
            return $rs;
        }

        //获取 aws s3 文件系统对象
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
        $bucket = data_get($configData, 'bucket_name', '-1');

        $cdnDomains = data_get($configData, 'host.' . $bucket . '.' . $resourceType, []);

        if (is_array($domain)) {
            $cdnDomains = array_merge($cdnDomains, $domain);
        }

        return array_values(array_filter(array_unique($cdnDomains)));
    }

}
