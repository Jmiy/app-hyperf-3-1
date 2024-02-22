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

namespace Business\Hyperf\Exception\Handler;

use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Exception\BusinessException;
use Hyperf\Context\Context;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Business\Hyperf\Utils\Response;
use Business\Hyperf\Utils\Monitor\Contract;

class AppExceptionHandler extends ExceptionHandler
{
    protected StdoutLoggerInterface $logger;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 获取统一格式异常数据
     * @param Throwable $exception 异常
     * @param bool $debug 是否debug
     * @return array
     */
    public static function getMessage(Throwable $throwable, $businessData = [], $level = 'error')
    {
        //获取平台 优先从上下文中获取，如果没有就通过$stackTrace匹配关键字获取
        $task = Context::get(Constant::CONTEXT_TASK_DATA);
        $platform = data_get($task, Constant::DB_COLUMN_PLATFORM, '');
        $stackTrace = $throwable->getTraceAsString();
        if (empty($platform)) {
            $platformData = array_keys(config(Constant::DB_COLUMN_PLATFORM));
            foreach ($platformData as $_platform) {
                if (false !== strpos($stackTrace, $_platform)) {
                    $platform = $_platform;
                    break;
                }
            }
        }

        return [
            Constant::CODE => $throwable->getCode(),
            Constant::EXCEPTION_MSG => $throwable->getMessage(),
            Constant::DB_COLUMN_TYPE => get_class($throwable),
            Constant::UPLOAD_FILE_KEY => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'business_data' => $businessData,//关联数据
            'stack_trace' => $stackTrace,
            'server_ip' => getInternalIp(),//服务器ip
            'http_code' => $throwable->getCode() ? $throwable->getCode() : -101,
            'level' => $level,
            Constant::DB_COLUMN_PLATFORM => $platform,
        ];
    }

    /**
     * 记录异常日志，并根据配置发送异常监控信息
     * @param Throwable $throwable 异常
     */
    public function log(Throwable $throwable, $level = 'error', $businessData = [])
    {
        $this->logger->{$level}(sprintf('%s [%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        $this->logger->{$level}('businessData:' . json_encode($businessData, JSON_UNESCAPED_UNICODE));
        $this->logger->{$level}($throwable->getTraceAsString());

//        if($throwable instanceof BusinessException){
//            //此处 调刊登接口写入 异常日志
//            var_dump('业务异常'.get_class($throwable));
//        }

        $enableAppExceptionMonitor = config('monitor.enable_app_exception_monitor', false);
        if ($enableAppExceptionMonitor) {//如果开启异常监控，就通过消息队列将异常，发送到相应的钉钉监控群
            try {

                $exceptionData = static::getMessage($throwable, $businessData, $level);

                //添加系统异常监控
                $exceptionName = '[系统异常:' . $level . '] 服务器ip-->' . getInternalIp();
                $message = data_get($exceptionData, 'message', '');
                $code = data_get($exceptionData, 'code', -101);
                $robot = 'default';
                $simple = false;
                $isQueue = true;
                $parameters = [
                    $exceptionName,
                    $message,
                    $code,
                    data_get($exceptionData, 'file'),
                    data_get($exceptionData, 'line'),
                    $exceptionData,
                    $robot,
                    $simple,
                    $isQueue,
                ];
                Contract::handle('Ali', 'Ding', 'report', $parameters);

            } catch (Throwable $ex) {
            }
        }
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->log($throwable);

        return Response::getDefaultResponseData($throwable->getCode(), $throwable->getMessage(), null, 500);

//        return Response::json(...Response::getResponseData(
//            Response::getDefaultResponseData($throwable->getCode(), $throwable->getMessage(), null),
//            true,
//            500,
//            []
//        ));

//        $data = json_encode(Response::getDefaultResponseData($throwable->getCode(), $throwable->getMessage(), null), JSON_UNESCAPED_UNICODE);
//
//        return $response->withHeader('Server', 'Hyperf')->withStatus(500)->withBody(new SwooleStream($data));
//        return $response->withHeader('Server', 'Hyperf')->withStatus(500)->withBody(new SwooleStream('Internal Server Error.'));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
