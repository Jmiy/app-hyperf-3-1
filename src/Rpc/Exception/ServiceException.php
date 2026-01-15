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

namespace Business\Hyperf\Rpc\Exception;

use Throwable;

class ServiceException extends \RuntimeException
{
    private array $context = [];

    public function __construct(array $context, string $message, int $code = 0, Throwable $previous = null)
    {
        $this->setContext($context);
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取上下文
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * 设置上下文
     * @param array $context
     * @return $this
     */
    public function setContext(array $context): static
    {
        $this->context = $context;
        return $this;
    }
}
