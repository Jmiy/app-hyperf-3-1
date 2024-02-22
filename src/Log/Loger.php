<?php
declare(strict_types=1);

namespace Business\Hyperf\Log;

use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

class Loger
{
    /**
     * @param string $name
     * @param string|null $group
     * @return LoggerInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function get(string $name = 'app', ?string $group = 'default'):LoggerInterface
    {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $group);
    }
}
