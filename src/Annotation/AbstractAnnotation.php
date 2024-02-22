<?php

declare(strict_types=1);
/**
 * 自定义注解
 * @link     https://www.hyperf.wiki/3.0/#/zh-cn/annotation?id=%e5%88%9b%e5%bb%ba%e4%b8%80%e4%b8%aa%e6%b3%a8%e8%a7%a3%e7%b1%bb
 */

namespace Business\Hyperf\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation as HyperfAbstractAnnotation;

abstract class AbstractAnnotation extends HyperfAbstractAnnotation
{
    public function __construct(...$value)
    {
        $formattedValue = $this->formatParams($value);
        foreach ($formattedValue as $key => $val) {
            if (property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }
    }

    /**
     * @param mixed $value
     */
    protected function formatParams($value): array
    {
        if (isset($value[0])) {
            $value = $value[0];
        }
        if (! is_array($value)) {
            $value = ['value' => $value];
        }
        return $value;
    }

    protected function bindMainProperty(string $key, array $value)
    {
        $formattedValue = $this->formatParams($value);
        if (isset($formattedValue['value'])) {
            $this->{$key} = $formattedValue['value'];
        }
    }
}
