<?php

namespace Freimaurerei\ServiceClient\Helper;

/**
 * Class Cast
 * @package Freimaurerei\ServiceClient\Helper
 */
class Cast
{
    /**
     * @param mixed $value
     * @return int|null
     */
    public static function toInt($value)
    {
        return isset($value) ? intval($value) : null;
    }

    /**
     * @param mixed $value
     * @return float|null
     */
    public static function toFloat($value)
    {
        return isset($value) ? floatval($value) : null;
    }

    /**
     * @param mixed $value
     * @param string|null $type
     * @return array|null
     */
    public static function toArray($value, $type = null)
    {
        if (!isset($value)) {
            return null;
        }

        return self::arrayElements(array_values((array)$value), $type);
    }

    /**
     * @param array|null $value
     * @param string|null $type
     * @param string|null $keyType
     * @return object|null
     */
    public static function toAssociativeArray($value, $type = null, $keyType = null)
    {
        if (!isset($value) || !is_array($value)) {
            return null;
        }

        return (object)array_combine(
            static::arrayElements(array_keys($value), $keyType),
            static::toArray($value, $type)
        );
    }

    private static function arrayElements(array $array, $type)
    {
        return isset($type)
            ? array_map(
                function ($element) use ($type) {
                    settype($element, $type);
                    return $element;
                },
                $array
            )
            : $array;
    }
}