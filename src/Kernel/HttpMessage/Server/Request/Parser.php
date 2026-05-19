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

namespace Business\Hyperf\Kernel\HttpMessage\Server\Request;

use Hyperf\HttpMessage\Exception\BadRequestHttpException;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Business\Hyperf\Kernel\HttpMessage\Exception\InvalidArgumentException;

use function Hyperf\Support\call;

class Parser
{
    public static array $parsers = [
        'application/json' => JsonParser::class,
        'text/json' => JsonParser::class,
        'application/xml' => XmlParser::class,
        'text/xml' => XmlParser::class,
    ];

    /**
     * 解析请求参数
     * @param string $rawBody 请求参数
     * @param string $contentType 请求参数的类型
     * @param bool|null $associative
     * @param int $depth
     * @param int $flags
     * @return mixed
     */
    public static function parse(
        string $rawBody,
        string $contentType,
        ?bool  $associative = null,
        int    $depth = 512,
        int    $flags = 0
    ): mixed
    {
        $contentType = strtolower($contentType);
        if (!array_key_exists($contentType, static::$parsers)) {
            throw new InvalidArgumentException("The '{$contentType}' request parser is not defined.");
        }

        $parser = static::$parsers[$contentType];

        return call([$parser, __FUNCTION__], [$rawBody, $associative, $depth, $flags]);
    }

    public static function has(string $contentType): bool
    {
        return array_key_exists(strtolower($contentType), static::$parsers);
    }

    public static function normalizeParsedBody(
        array                   $data = [],
        ?ServerRequestInterface $request = null,
        ?bool                   $associative = null,
        int                     $depth = 512,
        int                     $flags = 0
    ): mixed
    {
        if (!$request) {
            return $data;
        }

        $rawContentType = $request->getHeaderLine('content-type');
        if (($pos = strpos($rawContentType, ';')) !== false) {
            // e.g. text/html; charset=UTF-8
            $contentType = strtolower(substr($rawContentType, 0, $pos));
        } else {
            $contentType = strtolower($rawContentType);
        }

        try {
            if (static::has($contentType) && $rawBody = (string)$request->getBody()) {
                $data = static::parse($rawBody, $contentType, $associative, $depth, $flags);
            }
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), request: $request);
        } catch (BadRequestHttpException $exception) {
            throw $exception->setRequest($request);
        }

        return $data;
    }


}
