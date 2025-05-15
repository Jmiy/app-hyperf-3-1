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

namespace Business\Hyperf\Service\Distributed\Transaction;

use Business\Hyperf\Service\Traits\Base;

class Contract
{
    use Base;

    /**
     * 执行服务
     * @param string $provider 平台
     * @param string|array $serviceProvider 服务提供者
     * @param string $method 执行方法
     * @param array|null $parameters 参数
     * @return mixed
     */
    public static function handle(string $provider, string|array $serviceProvider, string $method, ?array $parameters = []): mixed
    {
        $_service = '';
        switch ($serviceProvider) {
            case 'Product':
                $_service = 'Product';
                break;

            case 'Base':
                $_service = '';
                $serviceProvider = 'BaseService';
                break;

            default:

                break;
        }

        return static::managerHandle($provider, (is_array($serviceProvider) ? $serviceProvider : [$_service, $serviceProvider]), $method, $parameters);

    }

}
