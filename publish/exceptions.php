<?php

declare(strict_types=1);

use Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler;
use Business\Hyperf\Exception\Handler\AppExceptionHandler;
use Hyperf\Validation\ValidationExceptionHandler;
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    'handler' => [
        'http' => [
            HttpExceptionHandler::class,
            AppExceptionHandler::class,
            ValidationExceptionHandler::class,
        ],
    ],
];
