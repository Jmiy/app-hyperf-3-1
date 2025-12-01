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

use Hyperf\Collection\Arr;
use function Business\Hyperf\Utils\Collection\data_get;
use function Hyperf\Config\config;
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
use Hyperf\Context\RequestContext;
use Hyperf\HttpServer\Router\Dispatched;
use Carbon\Carbon;

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

        $serverName = '';
        $serverPost = '';
        $serverHost = '';
        $url = '';
        $requestData = [];

        $request = RequestContext::get();
        if (!empty($request)) {
            $routeInfo = $request->getAttribute(Dispatched::class);
            $serverName = data_get($routeInfo, ['serverName'], 'http');
            $serverHost = $request->getHeaderLine('host');

            $route = data_get($routeInfo, ['handler', 'route'], '');//客户端请求的uri
            $url = $serverHost . $route;

            $serverConfig = [];
            $servers = config('server.servers', []);
            foreach ($servers as $_server) {
                if ($_server['name'] === $serverName) {
                    $serverConfig = $_server;
                    break;
                }
            }
            $serverPost = data_get($serverConfig, ['port']);//服务端监听的端口号

            $requestData = Arr::collapse([$request->getQueryParams(), $request->getParsedBody()]);
        }

        $context = Arr::collapse([$requestData, $businessData]);//关联数据

        return [
            'time' => Carbon::now()->toDateTimeString(),
            'Exception' => '[系统异常:' . $level . ']',
            'clientIp' => getClientIP(),
            'serverHost' => $serverHost,
            'serverIp' => getInternalIp(),//服务器ip
            'serverPost' => $serverPost,
            'serverName' => $serverName,
            'serverApp' => config('app_name'),
            'serverAppEnv' => config('app_env'),
            'url' => $url,
            Constant::UPLOAD_FILE_KEY => $throwable->getFile(),
            'line' => $throwable->getLine(),
            Constant::CODE => $throwable->getCode(),
            Constant::EXCEPTION_MSG => $throwable->getMessage(),
            Constant::DB_COLUMN_TYPE => get_class($throwable),
            'level' => $level,
            Constant::DB_COLUMN_PLATFORM => $platform,
            'break' => '',
            'stackTrace' => $stackTrace,
            'http_code' => $throwable->getCode() ? $throwable->getCode() : -101,
            'context' => $context,//关联数据
        ];
    }

    /**
     * 记录异常日志，并根据配置发送异常监控信息
     * @param Throwable $throwable 异常
     */
    public function log(Throwable $throwable, $level = 'error', $businessData = [])
    {
        $context = [
            'businessData' => $businessData,
            'stackTraces' => $throwable->getTraceAsString(),
        ];
        $this->logger->{$level}(sprintf('%s(%s)：[code:%s][message:%s]', $throwable->getFile(), $throwable->getLine(), $throwable->getCode(), $throwable->getMessage()), $context);

        $enableAppExceptionMonitor = config('monitor.enable_app_exception_monitor', false);
        if ($enableAppExceptionMonitor) {//如果开启异常监控，就通过消息队列将异常，发送到相应的钉钉监控群
            try {

                $messageData = static::getMessage($throwable, $businessData, $level);

                //添加系统异常监控
                $exceptionName = '[系统异常:' . $level . ']';
                $message = data_get($messageData, [Constant::EXCEPTION_MSG], '');
                $code = data_get($messageData, [Constant::CODE], -101);
                $robot = 'default';
                $simple = config('monitor.simple', false);//是否简单预警 true：是  false：否  默认：false
                $isQueue = config('monitor.isQueue', true);//是否压入队列 true：是  false：否  默认：true
                $delay = config('monitor.delay', null);//延迟执行时间  null：0-10随机 非null：对应延迟执行秒数 默认：null
                $parameters = [
                    $exceptionName,
                    $message,
                    $code,
                    data_get($messageData, ['file']),
                    data_get($messageData, ['line']),
                    $messageData,
                    $robot,
                    $simple,
                    $isQueue,
                    $delay,
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
