<?php

declare(strict_types=1);
/**
 * Job
 */

namespace Business\Hyperf\Job;

use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Service\Log\LogService;
use Carbon\Carbon;
use Business\Hyperf\Exception\Handler\AppExceptionHandler as ExceptionHandler;

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
        $messages = [
            'Time:' . Carbon::now()->toDateTimeString(),
            'Url:' . $this->url,
            'Exception:' . $this->exception,
            'Message:' . $this->message,
        ];

        if ($this->code) {
            $messages = [
                'Time:' . Carbon::now()->toDateTimeString(),
                'Url:' . $this->url,
                'Exception:' . $this->exception,
                'File：' . $this->file,
                'Line：' . $this->line,
                'Code：' . $this->code,
                'Message:' . $this->message,
                $this->simple ? '' : ('Exception Trace:' . (is_array($this->trace) ? json_encode($this->trace, JSON_UNESCAPED_UNICODE) : $this->trace)),
            ];
        }

        $data = [
            'code' => $this->code,
            'message' => $this->message,
            'file' => $this->file,
            'line' => $this->line,
            'business_data' => json_encode(data_get($this->trace, 'business_data', []), JSON_UNESCAPED_UNICODE),
            'stack_trace' => data_get($this->trace, 'stack_trace', ''),
            'server_ip' => data_get($this->trace, 'server_ip', ''),
            'level' => data_get($this->trace, 'level', ''),
            'client_ip' => data_get($this->trace, 'client_ip', ''),
        ];

        LogService::insertData('Log', [data_get($this->trace, Constant::DB_COLUMN_PLATFORM, ''), date('Ymd')], $data);

        $dingCodeData = explode(',', config('ding.' . $this->robot . '.code', ''));
        if (in_array('all', $dingCodeData) || in_array($this->code, $dingCodeData)) {
            $dingTalk = ding();
            if ($this->robot !== 'default') {
                $dingTalk->with($this->robot)->text(implode(PHP_EOL, $messages));
            } else {
                $dingTalk->text(implode(PHP_EOL, $messages));
            }
        }
    }

}
