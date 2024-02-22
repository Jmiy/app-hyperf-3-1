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

namespace Business\Hyperf\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

#[Constants]
class ErrorCode extends AbstractConstants
{
    /**
     * @Message("Server Error！")
     */
    public const SERVER_ERROR = 500;

    /**
     * @Message("Task does not exist！")
     */
    public const ERROR_TASK_NOT_EXIST = 100000;

    /**
     * @Message("Task does not exist！")
     */
    public const ERROR_SHOPEE = 100001;

    /**
     * @Message("Task does not exist！")
     */
    public const ERROR_JOOM = 100002;

    /**
     * @Message("Task does not exist！")
     */
    public const ERROR_AMAZON = 100003;

    /**
     * @Message("Task does not exist！")
     */
    public const ERROR_EBAY = 100004;

    /**
     * @Message("Task does not exist！")
     */
    public const ERROR_WISH_INSERT = 1001;
    /**
     * @Message("Task does not exist！")
     */
    public const ERROR_WISH_REDIS_LIST = 1002;

    /**
     * @Message("Task does not exist！")
     */
    public const ERROR_WISH_REQUEST = 1003;
    /**
     * @Message("Task does not exist！")
     */
    public const ERROR_WISH_REPORT = 1004;

    /**
     * @Message("Task does not exist！")
     */
    public const ERROR_ALIEXPRESS = 3001;

    /**
     * @Message("Lazada error！")
     */
    public const ERROR_LAZADA = 5001;

    /**
     * @Message("Walmart error！")
     */
    public const ERROR_WALMART = 20001;

    /**
     * @Message("Token expiry！")
     */
    public const ERROR_TOKEN = 403;

    /**
     * @Message("Daraz error！")
     */
    public const ERROR_DARAZ = 30021001;
    /**
     * @Message("Zoodmall error！")
     */
    public const ERROR_ZOODMALL = 60021001;
    /**
     * @Message("Catch error！")
     */
    public const ERROR_CATCH = 60061001;

    /**
     * @Message("Jdglobalsales error！")
     */
    public const ERROR_JDGLOBALSALES = 30022001;

    /**
     * @Message("Jumia error！")
     */
    public const ERROR_JUMIA = 60025001;
}
