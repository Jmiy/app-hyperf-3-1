<?php

/**
 * Base trait
 * User: Jmiy
 * Date: 2020-09-03
 * Time: 09:27
 */

namespace Business\Hyperf\Service\Traits;

use function Hyperf\Support\call;
use function Hyperf\Config\config;
use function Business\Hyperf\Utils\Collection\data_get;
use Throwable;
use Hyperf\Coroutine\Coroutine;
use ZipArchive;
use Hyperf\Collection\Arr;
use function Hyperf\Coroutine\go;
use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Constants\ErrorCode;
use Business\Hyperf\Exception\BusinessException;
use Business\Hyperf\Utils\Support\Facades\HttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Hyperf\Guzzle\RetryMiddleware;

trait BaseFile
{

    public static function getFileSize($fileSize, $acceptRanges)
    {
        switch (true) {
            case (false !== stripos($acceptRanges, 'KB'))://文件大小单位：KB
                $fileSize = $fileSize * 1024;

                break;

            case (false !== stripos($acceptRanges, 'MB'))://文件大小单位：MB
                $fileSize = $fileSize * 1024 * 1024;

                break;
            case (false !== stripos($acceptRanges, 'GB'))://文件大小单位：GB
                $fileSize = $fileSize * 1024 * 1024 * 1024;

                break;

            default:
                break;
        }

        return $fileSize;
    }

    /**
     * 下载文件
     * @param string $url 文件地址
     * @param string $reportDocumentId 报告文档id
     * @param string|null $path 报告保存的路径
     * @param string|null $compressionAlgorithm 压缩算法 默认：gzip
     * @param bool|null $latest 是否下载最新的文件 true:是 false:否   默认:true
     * @param int|null $tryFileSizeMaxNum 获取文件大小最大重试次数
     * @param int|null $tryDownFileMaxNum 下载文件最大重试次数
     * @return array|false
     * @throws \Throwable
     */
    public static function downFile(
        string  $url,
        string  $reportDocumentId,
        ?string $path = '',
        ?string $compressionAlgorithm = 'GZIP',
        ?bool   $latest = true,
        ?int    $tryFileSizeMaxNum = 3,
        ?int    $tryDownFileMaxNum = 3,
        ?string $srcFileName = null,
        ?int    $srcFileSize = null,
        ?string $distFileName = null,
        ?int    $distFileSize = null,
    )
    {
        $path = config('common.storage') . '/' . date('Ymd') . $path;
        $compressionAlgorithm = strtoupper($compressionAlgorithm);
        $arr = parse_url($url);
        $srcFileName = !empty($srcFileName) ? $srcFileName : ($path . '/' . $reportDocumentId . '-' . basename($arr['path']));//源文件
        $distFileName = !empty($distFileName) ? $distFileName : substr($srcFileName, 0, strlen($srcFileName) - 3);//目标文件

        $responseData = [];
        $fileSize = null;//源文件大小
        $responseStatusCode = null;
        if (file_exists($srcFileName)) {
            $_srcFileSize = filesize($srcFileName);
            if ($srcFileSize !== null && $_srcFileSize >= $srcFileSize) {//如果源文件已经下载成功，就跳过下载
                goto beginningCompressionAlgorithm;
            }
        }

        if (!$latest && is_file($distFileName)) {
            return [
                Constant::CODE => Constant::CODE_SUCCESS,
                Constant::URL => $distFileName,
            ];
        }

        /***************创建存放文件的文件夹 start ****************************/
        $tryMkdirNum = 0;
        beginningMkdir:
        try {
            //判断文件路径是否存在
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        } catch (Throwable $e) {
            if ($tryMkdirNum < 3) {
                ++$tryMkdirNum;
                Coroutine::sleep(rand(1, 10) * 0.5);
                goto beginningMkdir;
            }
            throw $e;
        }
        /***************创建存放文件的文件夹 end ****************************/

        /*****************获取文件大小 start *************************/
        $tryFileSizeNum = 0;
        beginningFileSize:
        $responseStatusCode = null;
        try {
            $options = [];
            $method = 'HEAD';
            $responseData = static::httpRequest($url, $options, $method);
            $responseStatusCode = data_get($responseData, Constant::RESPONSE_STATUS_CODE);
            if ($responseStatusCode == 200) {//如果接口正常返回，就从请求头获取文件大小
                $acceptRanges = data_get($responseData, Constant::RESPONSE_HEADERS . '.accept-ranges.0', 'bytes');//accept-ranges
                $fileSize = data_get($responseData, Constant::RESPONSE_HEADERS . '.content-length.0');//accept-ranges
                $fileSize = static::getFileSize($fileSize, $acceptRanges);
            }
        } catch (Throwable $e) {
            if ($tryFileSizeNum < $tryFileSizeMaxNum) {
                ++$tryFileSizeNum;
                Coroutine::sleep(rand(1, 10) * 0.5);
                goto beginningFileSize;
            }

            go(function () use ($e) {
                throw $e;
            });
        }
        /*****************获取文件大小 end *************************/

        /***************下载文件 start ****************************/
        $tryDownFileNum = 0;
        $tryNum = 0;

        beginning:

        $responseData = [];
        try {
            if ($responseStatusCode !== null && $responseStatusCode != 200) {
                $options = [];
                $method = 'GET';
                $responseData = static::httpRequest($url, $options, $method);
                $responseBody = data_get($responseData, Constant::RESPONSE_BODY, '');

                //把文件保存到服务器
                $rs = file_put_contents($srcFileName, $responseBody);
                if ($rs === false) {
                    return false;
                }

                $acceptRanges = data_get($responseData, Constant::RESPONSE_HEADERS . '.accept-ranges.0', 'bytes');//accept-ranges
                $fileSize = data_get($responseData, Constant::RESPONSE_HEADERS . '.content-length.0');//accept-ranges
                $fileSize = static::getFileSize($fileSize, $acceptRanges);

            } else {

                $handle = fopen($url, 'r');
                if (!$handle) {
                    return false;
                }
                $putResult = file_put_contents($srcFileName, $handle);
                $closeResult = fclose($handle);
                if (!$closeResult) {
                    return false;
                }
                if ($putResult === false) {
                    return false;
                }
            }
        } catch (Throwable $e) {
            if ($tryDownFileNum < $tryDownFileMaxNum) {//如果下载失败，重试 $tryDownFileMaxNum
                ++$tryDownFileNum;
                Coroutine::sleep(rand(1, 10) * 0.5);
                goto beginning;
            } else {

                //删除源文件
                if (is_file($srcFileName)) {
                    unlink($srcFileName);
                }

                throw $e;
            }
        }
        /***************下载文件 end ****************************/

        $srcfileSize = file_exists($srcFileName) ? filesize($srcFileName) : 0;
        if ($fileSize !== null && $srcfileSize < $fileSize - 1024) {//如果文件误差不在1KB范围内，就尝试下载 $tryDownFileMaxNum 次
            if ($tryNum < $tryDownFileMaxNum) {

                ++$tryNum;
                $tryDownFileNum = 0;

                goto beginning;
            }
        }

        beginningCompressionAlgorithm:

        //解压文件
        if ($compressionAlgorithm) {

            //删除目标文件
            if (is_file($distFileName)) {
                unlink($distFileName);
            }

            $output = '';
            $return_code = '';
            switch ($compressionAlgorithm) {
                case 'GZIP':
//                    $stream = gzopen($srcFileName, "r");
//                    while (!gzeof($stream)) { //逐行读取
//                        file_put_contents($distFileName, gzread($stream, 10000), FILE_APPEND);
//                    }
//                    gzclose($stream);

                    //解压文件
                    exec("gzip -dc '{$srcFileName}' > '{$distFileName}'", $output, $return_code);
                    if ($return_code) {
                        throw new BusinessException(
                            100003,
                            'uncompress_failure-->output--->' . static::pack($output) . '---->return_code-->' . static::pack($return_code)
                        );
                    }

                    break;

                case 'ZIP':

                    $zip = new ZipArchive();  // 创建 ZipArchive 对象
                    $zip->open($srcFileName);  // 打开 zip 包
                    $zip->extractTo($path);  // 把 zip 包内的所有文件解压到指定目录
                    $file_name = $zip->getNameIndex(0);
                    $zip->close();  // 关闭打开的 zip 包

                    $distFileName = $path . '/' . $file_name;
                    if (!is_file($distFileName)) {
                        throw new BusinessException(ErrorCode::ERROR_WALMART, "文件解压保存本地失败");
                    }

                    break;

                default:
                    break;
            }

            //删除源文件
//            if (is_file($srcFileName)) {
//                unlink($srcFileName);
//            }
        } else {
            $distFileName = $srcFileName;
        }

        $fileSize = file_exists($srcFileName) ? filesize($srcFileName) : 0;
        $distFileSize = file_exists($distFileName) ? filesize($distFileName) : 0;

        return Arr::collapse([
            [
                Constant::CODE => $responseStatusCode,
                Constant::URL => $distFileName,
                'distfileSize' => $distFileSize,//目标文件大小
                'srcFileName' => $srcFileName,//源文件地址
                'fileSize' => $fileSize,//源文件大小
            ], $responseData
        ]);
    }

    /**
     * 读文件
     * @param string $url
     * @param $callback
     * @param int|float|null $block
     */
    public static function readFile(string $url, $headerCallback, $callback, ?int $block = 1024 * 1024)
    {
        $stream = fopen($url, "r");
        if ($stream) {
            $left = '';
            $header = [];
            while (!feof($stream)) {
                // read the file
                $temp = fread($stream, $block);
                $data = explode("\n", $temp);
                $data[0] = $left . $data[0];
                if (!feof($stream)) {
                    $left = array_pop($data);
                }

                if ($headerCallback && empty($header)) {
                    $header = call($headerCallback, [$data[0]]);
                    unset($data[0]);
                }

                if ($callback) {
                    go(function () use ($callback, $data, $header) {
                        call($callback, [$data, $header]);
                    });
                }
            }
        }
        fclose($stream);
    }

}
