<?php

namespace Freimaurerei\ServiceClient\Helper;

use Freimaurerei\ServiceModel\Validators\ArrayValidator;
use Freimaurerei\ServiceModel\Validators\CastValidator;

/**
 * Class Model
 * @package Freimaurerei\ServiceClient\Helper
 */
class Model
{
    const ARRAY_TYPE_PATTERN = '/\[.*\]\z/';

    private static $_knownCasters = [
        'string' => CastValidator::CAST_STRING,
        'int' => CastValidator::CAST_INT,
        'integer' => CastValidator::CAST_INT,
        'boolean' => CastValidator::CAST_BOOL,
        'bool' => CastValidator::CAST_BOOL,
        'float' => CastValidator::CAST_FLOAT,
        'double' => CastValidator::CAST_FLOAT,
        'array' => CastValidator::CAST_ARRAY,
    ];

    private static $_knownTypes = [
        'string' => ArrayValidator::TYPE_STRING,
        'int' => ArrayValidator::TYPE_INT,
        'integer' => ArrayValidator::TYPE_INT,
        'boolean' => ArrayValidator::TYPE_BOOL,
        'bool' => ArrayValidator::TYPE_BOOL,
        'float' => ArrayValidator::TYPE_FLOAT,
        'double' => ArrayValidator::TYPE_FLOAT,
    ];

    /**
     * @param string $type
     * @return bool
     */
    public static function isAnnotationTypeArray($type)
    {
        return strcasecmp($type, 'array') === 0 || preg_match(self::ARRAY_TYPE_PATTERN, $type);
    }

    /**
     * Возвращает каноническое наименование примитивного типа
     *
     * Если передается тип-массив, то значение переменной преобразуется в наименование типа без квадратных скобок "[]"
     *
     * @param string $type
     * @param string $keysType
     * @return string|null
     */
    public static function getCanonicalType(&$type, &$keysType = null)
    {
        if (preg_match('/\[(.+)\]\z/', $type, $matches)) {
            $keysType = trim(strtolower($matches[1]));
            $keysType = self::getCanonicalType($keysType);
        } else {
            $keysType = null;
        }

        $type = preg_replace(self::ARRAY_TYPE_PATTERN, '', $type);
        return isset(self::$_knownTypes[$type]) ? self::$_knownTypes[$type] : null;
    }

    /**
     * Проверяет является ли тип примитивом
     *
     * Если передается тип-массив, то значение переменной преобразуется в наименование типа без квадратных скобок "[]"
     *
     * @param string $type
     * @return bool
     */
    public static function isPrimitive(&$type)
    {
        return self::getCanonicalType($type) || self::isAnnotationTypeArray($type)
            || $type === ArrayValidator::TYPE_MIXED;
    }

    /**
     * Возвращает полное наименование класса-кастера
     *
     * @param string $type
     * @return string|null
     */
    public static function getTypeCaster($type)
    {
        if (self::isAnnotationTypeArray($type)) {
            return CastValidator::CAST_ARRAY;
        }

        return isset(self::$_knownCasters[$type]) ? self::$_knownCasters[$type] : null;
    }
}