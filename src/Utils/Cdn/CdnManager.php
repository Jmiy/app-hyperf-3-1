<?php

namespace Business\Hyperf\Utils\Cdn;

use function Hyperf\Collection\data_set;
use Hyperf\Collection\Arr;
use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Utils\Response;
use Business\Hyperf\Exception\Handler\AppExceptionHandler as ExceptionHandler;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Hyperf\HttpServer\Contract\RequestInterface;

class CdnManager {

    /**
     * 获取默认的响应数据结构
     * @param int $code 响应状态码
     * @param string $msg 响应提示
     * @param array $data 响应数据
     * @return array $data
     */
    public static function getDefaultResponseData($code = Constant::PARAMETER_INT_DEFAULT, $msg = Constant::PARAMETER_STRING_DEFAULT, $data = Constant::PARAMETER_ARRAY_DEFAULT) {

        $uploadData = [
            Constant::FILE_URL => '',
            Constant::FILE_TITLE => '',
            Constant::DB_COLUMN_TYPE => '',
            Constant::FILE_FULL_PATH => Constant::PARAMETER_STRING_DEFAULT,
            Constant::RESOURCE_TYPE => Constant::ORDER_STATUS_SHIPPED_INT, //资源类型 1:图片 2:视频 3:js 4:css 默认:1
            'cdnProviderData' => Constant::PARAMETER_ARRAY_DEFAULT,
        ];

        $data = Arr::collapse([$uploadData, $data]);

        return Response::getDefaultResponseData($code, $msg, $data);
    }

    /**
     * 上传文件
     * @param string $filePath 图片在服务器的绝对路径 或者 上传文件的key
     * @param UploadedFile|UploadedFile[]|RequestInterface|string|null $file 文件对象 或者 文件base64_encode字符串
     * @param string $vitualPath 虚拟路径
     * @param string $cdnProvider 云存储提供商
     * @param boolean $is_del  是否删除原文件  false:否  true：是  默认:false 
     * @param boolean $isCn    是否使用国内cdn  false:否  true：是  默认:false
     * @param string $fileName    文件名
     * @param int $resourceType 文件类型 1：图片 2：视频
     * @param array $extData 扩展数据
     * @return array
     */
    public static function upload($filePath = null, $file = null, $vitualPath = '/upload/img/', $cdnProvider = 'UploadCdn', $is_del = false, $isCn = false, $fileName = '', $resourceType = 1, $extData = Constant::PARAMETER_ARRAY_DEFAULT) {

        $data = [
            Constant::RESOURCE_TYPE => $resourceType, //资源类型 1:图片 2:视频 3:js 4:css 默认:1
        ];
        $rs = static::getDefaultResponseData(8, 'CDN提供商: ' . $cdnProvider . '不存在', $data);

        $cdnProvider = implode('', [__NAMESPACE__, '\\', $cdnProvider]);
        if (!class_exists($cdnProvider)) {//如果没有对应的CDN提供商，就直接返回原始url
            return $rs;
        }

        if ($file instanceof RequestInterface) {
            $request = $file;
            $file = $request->getUploadedFiles();
            if (empty($file)) {
                $file = $request->file($filePath, Constant::PARAMETER_STRING_DEFAULT);
            }
        } elseif (is_array($file)) {

        } else if (!is_string($file) && !($file instanceof UploadedFile)) {
            if (!is_file($filePath) || !file_exists($filePath)) {
                $filePath = parse_url($filePath, PHP_URL_PATH);
            }

            if (!is_file($filePath) || !file_exists($filePath)) {
                data_set($rs, Constant::CODE, 6);
                data_set($rs, Constant::MSG, $filePath . ' 文件不存在');
                return $rs;
            }

            if (!is_readable($filePath)) {
                data_set($rs, Constant::CODE, 7);
                data_set($rs, Constant::MSG, $filePath . ' 文件不可读');
                return $rs;
            }

            $file = new UploadedFile($filePath, (int) filesize($filePath),(int) UPLOAD_ERR_OK, $filePath);
        }

        return $cdnProvider::upload($filePath, $file, $vitualPath, $is_del, $isCn, $fileName, $resourceType, $extData);
    }

}
