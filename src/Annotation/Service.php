<?php

declare(strict_types=1);
/**
 * 自定义注解
 * @link     https://www.hyperf.wiki/3.0/#/zh-cn/annotation?id=%e5%88%9b%e5%bb%ba%e4%b8%80%e4%b8%aa%e6%b3%a8%e8%a7%a3%e7%b1%bb
 */

namespace Business\Hyperf\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Service extends AbstractAnnotation
{
}
