<?php
declare(strict_types=1);

namespace Business\Hyperf\Service\Log;

use Business\Hyperf\Model\Log\Log;
use Business\Hyperf\Service\BaseService;

class LogService extends BaseService
{

    /**
     * 获取模型别名
     * @return string
     */
    public static function getModelAlias()
    {
        return Log::class;
    }
}