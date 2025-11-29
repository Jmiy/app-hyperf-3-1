<?php

declare(strict_types=1);
/**
 * Job
 */

namespace Business\Hyperf\Job;

use Business\Hyperf\Utils\Support\Facades\QueueRedisDriver;
use Business\Hyperf\Utils\Support\Facades\Redis;
use Hyperf\Collection\Arr;
use function Business\Hyperf\Utils\Collection\data_get;
use function Hyperf\Config\config;
use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Service\Log\LogService;
use Carbon\Carbon;
use Business\Hyperf\Exception\Handler\AppExceptionHandler as ExceptionHandler;
use function Hyperf\Coroutine\go;

class DingDingJob extends Job
{

    /**
     * @var
     */
    private $message;

    /**
     * @var
     */
    private $code;

    /**
     * @var
     */
    private $file;

    /**
     * @var
     */
    private $line;

    /**
     * @var
     */
    private $url;

    /**
     * @var
     */
    private $trace;

    /**
     * @var
     */
    private $exception;

    /**
     * @var string
     */
    protected $robot = 'default';

    /**
     * @var
     */
    private $simple;

    /**
     * Create a new job instance.
     *
     * @param $url
     * @param $exception
     * @param $message
     * @param $code
     * @param $file
     * @param $line
     * @param $trace
     * @param $simple
     */
    public function __construct($url, $exception, $message, $code, $file, $line, $trace, $robot = 'default', $simple = false)
    {
        $this->message = $message;
        $this->code = $code;
        $this->file = $file;
        $this->line = $line;
        $this->url = $url;
        $this->trace = $trace;
        $this->exception = $exception;
        $this->robot = $robot;
        $this->simple = $simple;
    }

    /**
     * Execute the job.
     * ding()->at([],true)->text(implode(PHP_EOL, $message));//@所有人
     * @return void
     */
    public function handle()
    {
        $serverIp = data_get($this->trace, ['serverIp'], '');//服务器ip

        $messages = [];
        foreach ($this->trace as $key => $value) {
            if ($key == 'break') {
                break;
            }
            $messages[] = implode(':', [$key . ':' . $value]);
        }

//        $messages = [
//            'time:' . $time,
//            'Exception:' . $this->exception,
//            'serverHost：' . $serverHost,
//            'serverIp：' . $serverIp,
//            'serverPost：' . $serverPost,
//            'serverName: ' . $serverName,
//            'serverApp: ' . config('app_name'),
//            'serverAppEnv: ' . config('app_env'),
//            'Url:' . $url,
//            'File：' . $this->file,
//            'Line：' . $this->line,
//            'Code：' . $this->code,
//            'Message:' . $this->message,
//        ];

        if ($this->code && !$this->simple) {
            $messages[] = 'stackTrace:' . (is_array($this->trace) ? json_encode($this->trace, JSON_UNESCAPED_UNICODE) : $this->trace);
        }

        $data = [
            'code' => $this->code,
            'message' => $this->message,
            'file' => $this->file,
            'line' => $this->line,
            'business_data' => json_encode(data_get($this->trace, 'context', []), JSON_UNESCAPED_UNICODE),
            'stack_trace' => data_get($this->trace, 'stackTrace', ''),
            'server_ip' => data_get($this->trace, ['serverIp'], ''),//服务器ip
            'level' => data_get($this->trace, 'level', ''),
            'client_ip' => data_get($this->trace, 'clientIp', ''),
        ];

        LogService::insertData('Log', [data_get($this->trace, Constant::DB_COLUMN_PLATFORM, ''), date('Ymd')], $data);

        $dingConfig = Arr::collapse(
            [
                config('ding.' . $this->robot, []),
                config('ding.' . $this->robot . '-' . $this->code, []),
            ]
        );

        $dingCodeData = explode(',', data_get($dingConfig, ['code'], ''));
        if (in_array('all', $dingCodeData) || in_array($this->code, $dingCodeData)) {

            $nx = true;
            $poolName = data_get($dingConfig, ['poolName'], 'default');
            $ex = data_get($dingConfig, ['lockEx'], 3600);
            unset($data['business_data'], $data['stack_trace'], $data['client_ip']);
            $lockKeys = [md5(json_encode($data, JSON_UNESCAPED_UNICODE))];

            $distributedLockKey = QueueRedisDriver::getKey(
                ['ding'],
                [$this->code],
                $lockKeys
            );
            try {
                $redis = Redis::getRedis($poolName);

                //获取分布式锁
                $nx = $redis->set($distributedLockKey, 1, ['nx', 'ex' => $ex]);// Will set the key, if it doesn't exist, with a ttl of 10 seconds
//            //释放分布式锁
//            $rs = $redis->del($distributedLockKey);
            } catch (\Throwable $exception) {
//                go(function () use ($throwable) {
//                    throw $throwable;
//                });
            }

            if ($nx == true) {
                ding()->with($this->robot)->text(implode(PHP_EOL, $messages));
            }
        }
    }

}
