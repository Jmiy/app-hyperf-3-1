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

namespace Business\Hyperf\Kernel\Codec;

use Business\Hyperf\Kernel\Codec\Exception\InvalidArgumentException;
use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Xmlable;
use SimpleXMLElement;

class Xml
{
    /**
     * 编码为 xml
     * @param mixed $data
     * @param SimpleXMLElement|null $parentNode
     * @param string $root
     * @return string
     * @throws \Exception
     */
    public static function toXml(mixed $data, ?SimpleXMLElement $parentNode = null, string $root = 'root'): string|bool
    {
        if ($data instanceof Xmlable) {
            return (string)$data;
        }
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } else {
            $data = (array)$data;
        }
        if ($parentNode === null) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?>' . "<{$root}></{$root}>");
        } else {
            $xml = $parentNode;
        }
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                self::toXml($value, $xml->addChild($key));
            } else {
                if (is_numeric($key)) {
                    $xml->addChild('item' . $key, (string)$value);
                } else {
                    $xml->addChild($key, (string)$value);
                }
            }
        }
        return trim($xml->asXML());
    }

    /**
     * 解码
     * @param string $xml
     * @param bool|null $associative
     * @param int $depth
     * @param int $flags
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function toArray(string $xml, ?bool $associative = null, int $depth = 512, int $flags = 0): mixed
    {
        $respObject = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);

        if ($respObject === false) {
            throw new InvalidArgumentException('Syntax error.');
        }

        return Json::decode(Json::encode($respObject, 0), $associative, $depth, $flags);
    }
}
