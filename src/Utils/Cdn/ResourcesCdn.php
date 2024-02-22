<?php

namespace Business\Hyperf\Utils\Cdn;

use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Utils\Context;
use Business\Hyperf\Utils\Filesystem\Util;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Exception\ParallelExecutionException;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Hyperf\Stringable\Str;
use League\Flysystem\Config;
use League\Flysystem\Visibility;
use function Business\Hyperf\Utils\Collection\data_get;
use function Hyperf\Coroutine\parallel;

class ResourcesCdn
{

    public static $uriPrefix = []; // 图片路径前缀
    public static $imgUrlCn = ''; //国内
    public static $cdnDomains = []; //css域名

    /**
     * @param int $storeId
     * @return mixed|null
     */
    public static function getContextKey($storeId = 1)
    {
        $key = str_replace('.', '', md5(json_encode([static::class, $storeId])));
        return 'filesystem.' . $key;
    }

    /**
     * 设置配置信息
     * @param int $storeId 商城id
     * @param array $configData 配置数据
     */
    public static function setConf($storeId = 1, $configData = [])
    {

    }

    /**
     * 获取属性值
     * @param int $storeId 商城id
     * @param array $configData 配置数据
     * @param null|sting|array $key 属性名称
     * @return array|mixed
     */
    public static function getAttribute($storeId = 1, $key = null, $configData = [])
    {
        $configData = static::setConf($storeId, $configData);
        return data_get($configData, $key, '');
    }

    /**
     * 获取服务器地区
     * @return string 服务器地区
     */

    public static function getArea()
    {
        $_area = explode('_', config('app.serverarea', ''));
        $area = end($_area);
        return $area;
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

        $area = static::getArea();

        $cdnDomains = static::getAttribute(
            $storeId,
            ($area ? $area . '_' : '') . 'url' . ($resourceType ? '_' . $resourceType : '')
        );//key组成规则: area_url_1
        $cdnDomains = explode(',', $cdnDomains);

        if (is_array($domain)) {
            $cdnDomains = array_merge($cdnDomains, $domain);
        }

        return array_values(array_filter(array_unique($cdnDomains)));
    }

    /**
     * 获取资源域名
     * @param int $storeId 品牌id
     * @param int $resourceType 资源类型 0:所有 1:图片 2:视频 3:js 4:css 默认:1
     * @param array|null $domain cdn域名
     * @return array 图片cdn域名
     */
    public static function getResourceDomain($storeId = 1, $resourceType = 1, $isCn = false, $domain = null)
    {

        //获取资源cdn数据
        $cdnData = static::getResourceTypeDomain($storeId, $resourceType, $isCn, $domain);

        $num = count($cdnData);

        if ($num <= 0) {
            return '';
        }

        $contextId = static::getContextKey($storeId) . '.resourceKey';
        $_num = Context::getOrSet($contextId, $num);
        Context::set($contextId, $_num + 1);

        $key = $_num % $num;
        return $cdnData[$key];
    }

    /**
     * 获取资源地址
     * @param int $storeId 品牌id
     * @param string $resourceUrl 资源地址
     * @param int $resourceType 资源类型 0:所有 1:图片 2:视频 3:js 4:css 默认:0
     * @param boolean $isCn 是否使用国内cdn  false:否  true:是 默认：false
     * @param string $wh 宽*高
     * @param int $mode 缩略模式：0-5 详情：https://developer.qiniu.com/dora/manual/1279/basic-processing-images-imageview2
     * @return string
     */
    public static function getResourceUrl($storeId = 1, $resourceUrl = '', $resourceType = 0, $isCn = false, $wh = '', $mode = '0')
    {

        if (empty($resourceUrl)) {
            return '';
        }

        $resourceUrl = parse_url($resourceUrl, PHP_URL_PATH);
        $cdnData = static::getResourceDomain($storeId, $resourceType, $isCn);

        $url = rtrim($cdnData, '/') . '/' . ltrim($resourceUrl, '/');
        $urlParam = static::getUrlParam($wh, $mode);

        return $url . $urlParam;
    }

    /**
     * 获取图片 格式转换、缩略、剪裁 参数
     * @param string $wh 宽*高
     * @param int $mode 缩略模式：0-5 详情：https://developer.qiniu.com/dora/manual/1279/basic-processing-images-imageview2
     * @param string $quality 新图的图片质量  取值范围是[1, 100]，默认75。七牛会根据原图质量算出一个修正值，取修正值和指定值中的小值。
     *    注意：
     *    ● 如果图片的质量值本身大于90，会根据指定值进行处理，此时修正值会失效。
     *    ● 指定值后面可以增加 !，表示强制使用指定值，如100!。
     *    ● 支持图片类型：jpg。
     *    详情：https://developer.qiniu.com/dora/manual/1279/basic-processing-images-imageview2#1
     * @return string
     */
    public static function getUrlParam($wh = '', $mode = '0', $quality = '75')
    {
        $urlParam = '';

        if (empty($wh)) {
            return $urlParam;
        }

        $whData = explode('*', $wh);

        if ((isset($whData[0]) && $whData[0] != 0) || isset($whData[1]) && $whData[1] != 0) {
            $urlParam .= '?imageView2/' . $mode;
        }

        if (isset($whData[0]) && $whData[0] != 0) {
            $urlParam .= '/w/' . $whData[0];
        }

        if (isset($whData[1]) && $whData[1] != 0) {
            $urlParam .= '/h/' . $whData[1];
        }

        if ($urlParam) {
            $urlParam .= '/interlace/1/ignore-error/1/q/' . $quality;
        }

        return $urlParam;
    }

    /**
     * 获取上传到七牛所使用的文件URI
     * @param string $vitualPath 七牛虚拟路径
     * @param string $ext 文件后缀
     * @param string $fileName 文件名
     * @return string 文件URI
     */
    public static function getUploadFileName($resourceType = 1, $vitualPath = '', $ext = null, $fileName = '')
    {
        $fileName = static::getFileName($ext, $fileName);
        $filePath = static::getDistVitualPath($resourceType, $vitualPath);
        return implode('/', [$filePath, $fileName]);
    }

    /**
     * Returns locale independent base name of the given path.
     *
     * @param string $name The new file name
     *
     * @return string containing
     */
    public static function getName($name)
    {
        $originalName = str_replace('\\', '/', $name);
        $pos = strrpos($originalName, '/');
        $originalName = false === $pos ? $originalName : substr($originalName, $pos + 1);

        return $originalName;
    }

    public static function uploadBase64File($file = null, $vitualPath = '', $is_del = false, $isCn = false, $fileName = '', $resourceType = 1, $extData = Constant::PARAMETER_ARRAY_DEFAULT)
    {

        $diskData = static::getDisk($extData);
        if (data_get($diskData, Constant::CODE, 0) != 1) {
            return $diskData;
        }
        $filesystem = data_get($diskData, Constant::DATA, null);

        $_data = [
            Constant::RESOURCE_TYPE => $resourceType, //资源类型 1:图片 2:视频 3:js 4:css 默认:1
        ];
        $rs = static::getDefaultResponseData(Constant::ORDER_STATUS_SHIPPED_INT, Constant::PARAMETER_STRING_DEFAULT, $_data);

        $fileExtension = '.png';
        if (strpos($file, 'data:image/png;base64') !== false) {
            $data = explode(',', $file); //data:image/png;base64,iVBORw0KGgoAAAANSUhEU
            $fileContents = base64_decode(end($data));

            $fileExtension = explode('/', $data[0]); //data:image/png;base64,
            unset($data);
            $fileExtension = explode(';', $fileExtension[1]);
            $fileExtension = '.' . $fileExtension[0];
        } else {
            $fileExtension = data_get($extData, ['fileExtension'], $fileExtension);
            $fileContents = base64_decode($file);
        }

        $path = static::getUploadFileName($resourceType, $vitualPath, $fileExtension, $fileName);

        $config = [
            Config::OPTION_VISIBILITY => Visibility::PUBLIC,
            Config::OPTION_DIRECTORY_VISIBILITY => Visibility::PUBLIC,
            //'mimetype'=>'',
        ];
        $filesystem->write(
            $path,
            $fileContents,
            $config
        );

        $storeId = data_get($extData, Constant::DB_COLUMN_SITE_ID, 0);
        $url = static::getResourceUrl($storeId, $path, $resourceType, $isCn);

        data_set($rs, Constant::DATA . Constant::LINKER . Constant::FILE_URL, $url);
        data_set($rs, Constant::DATA . Constant::LINKER . Constant::FILE_FULL_PATH, $url);
        data_set($rs, Constant::CODE, Constant::ORDER_STATUS_SHIPPED_INT);
        data_set($rs, Constant::MSG, Constant::PARAMETER_STRING_DEFAULT);

        return $rs;
    }

    /**
     * 上传文件
     * @param string $filePath 图片在服务器的绝对路径
     * @param string $resourceType 文件类型 1：图片 2：视频
     * @param string $vitualPath 云存储虚拟路径
     * @param boolean $is_del 是否删除原文件  false:否  true：是  默认:false
     * @param boolean $isCn 是否使用国内cdn  false:否  true：是  默认:false
     * @return array 上传结果
     */
    public static function upload($filePath, $files = null, $vitualPath = '', $is_del = false, $isCn = false, $fileName = '', $resourceType = 1, $extData = Constant::PARAMETER_ARRAY_DEFAULT)
    {
        $diskData = static::getDisk($extData);
        if (data_get($diskData, Constant::CODE, 0) != 1) {
            return $diskData;
        }

        $filesystem = data_get($diskData, Constant::DATA, null);

        $_data = [
            Constant::RESOURCE_TYPE => $resourceType, //资源类型 1:图片 2:视频 3:js 4:css 默认:1
        ];
        $rs = static::getDefaultResponseData(Constant::ORDER_STATUS_SHIPPED_INT, Constant::PARAMETER_STRING_DEFAULT, $_data);

        $files = is_array($files) ? $files : [$filePath => $files];
        $uploadData = [];
        $distVitualPath = static::getDistVitualPath($resourceType, $vitualPath);
        $storeId = data_get($extData, Constant::DB_COLUMN_SITE_ID, 0);


        $concurrent = 10;//count($itemIds);
        $callableData = [];
        foreach ($files as $key => $file) {
            $callableData[$key] = function () use ($filesystem, $rs, $distVitualPath, $storeId, $file, $vitualPath, $is_del, $isCn, $fileName, $resourceType, $extData) {

                if (is_array($file)) {
                    return static::upload('all', $file, $vitualPath, $is_del, $isCn, $fileName, $resourceType, $extData);
                }

                if (!($file instanceof UploadedFile)) {
//                    static::setFile(null);
                    return static::uploadBase64File($file, $vitualPath, $is_del, $isCn, $fileName, $resourceType, $extData);
                }

                $isValid = data_get($extData, 'isValid', true);
                if ($isValid && !$file->isValid()) {
                    data_set($rs, Constant::DATA . Constant::LINKER . Constant::FILE_URL, Constant::PARAMETER_STRING_DEFAULT);
                    data_set($rs, Constant::DATA . Constant::LINKER . Constant::FILE_FULL_PATH, Constant::PARAMETER_STRING_DEFAULT);
                    data_set($rs, Constant::CODE, 10031);
                    data_set($rs, Constant::MSG, $file->getError());//
                    return $rs;
                }

                $originalName = static::getName($file->getClientFilename());//原始文件名

                if (empty($fileName)) {
                    if (data_get($extData, 'use_origin_name', Constant::PARAMETER_INT_DEFAULT)) {//如果需要使用原始文件名，就获取客户原始文件名
                        $fileName = $originalName;
                    } else {
                        $extension = $file->getExtension();
                        $fileName = Str::random(10) . '.' . $extension;
                    }
                } else {
                    $fileName = Str::random(10) . $fileName;
                }

                $config = [
                    Config::OPTION_VISIBILITY => Visibility::PUBLIC,
                    Config::OPTION_DIRECTORY_VISIBILITY => Visibility::PUBLIC,
                    //'mimetype'=>'',
                ];
                $path = static::normalizePath(implode('/', [$distVitualPath, $fileName]));
                $stream = fopen($file->getRealPath(), 'r+');
                $filesystem->writeStream(
                    $path,
                    $stream,
                    $config
                );
                if (is_resource($stream)) {
                    fclose($stream);
                }

                $url = static::getResourceUrl($storeId, $path, $resourceType, $isCn);
                data_set($rs, Constant::DATA . Constant::LINKER . Constant::FILE_URL, $url);
                data_set($rs, Constant::DATA . Constant::LINKER . Constant::FILE_FULL_PATH, $url);
                data_set($rs, Constant::CODE, Constant::ORDER_STATUS_SHIPPED_INT);
                data_set($rs, Constant::MSG, Constant::PARAMETER_STRING_DEFAULT);
                data_set($rs, Constant::DATA . Constant::LINKER . 'originalName', $originalName);

                return $rs;
            };
        }

        if (empty($callableData)) {//如果没有消息，就直接返回
            return [];
        }

        $tryNum = 0;

        beginning:

        $throwables = [];
        $responseData = [];
        try {
            $responseData = parallel($callableData, $concurrent);
        } catch (ParallelExecutionException $e) {
            $responseData = $e->getResults();// 获取协程中的返回值。
            $throwables = $e->getThrowables(); //获取协程中出现的异常。
        }

        $uploadData = $responseData + $uploadData;

        if ($throwables) {
            $throwablesMessageDetailIds = array_keys($throwables);
            foreach ($callableData as $messageDetailId => $item) {
                if (!in_array($messageDetailId, $throwablesMessageDetailIds)) {
                    unset($callableData[$messageDetailId]);
                }
            }
            Coroutine::sleep(rand(1, 2));
            if ($tryNum <= 2) {
                $tryNum++;
                goto beginning;
            }

            $uploadData = $throwables + $uploadData;

        }

        return data_get($uploadData, [$filePath], $uploadData);
    }

    /**
     * @param $url
     * @return array
     * 删除空间文件
     */
    public static function deleteFiles($url)
    {

    }

    /**
     * 获取默认的响应数据结构
     * @param int $code 响应状态码
     * @param string $msg 响应提示
     * @param array $data 响应数据
     * @return array $data
     */
    public static function getDefaultResponseData($code = Constant::PARAMETER_INT_DEFAULT, $msg = Constant::PARAMETER_STRING_DEFAULT, $data = Constant::PARAMETER_ARRAY_DEFAULT)
    {
        return CdnManager::getDefaultResponseData($code, $msg, $data);
    }

    /**
     * 获取目的云存储虚拟路径
     * @param int $resourceType 文件类型 1：图片 2：视频
     * @param string $vitualPath 云存储虚拟路径
     * @return string 目的云存储虚拟路径
     */
    public static function getDistVitualPath($resourceType = 1, $vitualPath = '')
    {
        $path = implode('/', [
            (isset(static::$uriPrefix[$resourceType]) && static::$uriPrefix[$resourceType] ? static::$uriPrefix[$resourceType] : ''),
            $vitualPath,
            date('Ymd')
        ]);
        return static::normalizePath($path);
    }

    public static function setFile($file = null, $storeId = 0)
    {
        $contextId = static::getContextKey($storeId) . '.file';
        return Context::set($contextId, $file);
    }

    public static function getFile($storeId = 0)
    {
        $contextId = static::getContextKey($storeId) . '.file';
        return Context::get($contextId);
    }

    /**
     * 获取文件名
     * @param string $ext
     * @param string $fileName
     * @return string 文件名
     */
    public static function getFileName($ext = null, $fileName = '')
    {
        $fileExt = $ext;
        if (empty($fileExt)) {
            $file = static::getFile();
            $fileExt = $file ? ('.' . $file->getExtension()) : '';
        }

        return $fileName ? $fileName : (time() . rand(100, 999) . $fileExt);
    }

    /**
     * Normalize path.
     *
     * @param string $path
     *
     * @return string
     * @throws LogicException
     *
     */
    public static function normalizePath($path)
    {
        return Util::normalizePath($path);
    }

}
