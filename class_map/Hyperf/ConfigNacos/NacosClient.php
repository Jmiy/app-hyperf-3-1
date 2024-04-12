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
namespace Hyperf\ConfigNacos;

use Hyperf\Nacos\Application;
use Hyperf\Nacos\Config;

class NacosClient extends Application
{
    public function getConfig(): Config
    {
        return $this->config;
    }
}
