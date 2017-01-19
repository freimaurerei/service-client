<?php

/** @noinspection PhpUndefinedClassInspection */

namespace Freimaurerei\ServiceClient;

/**
 * Class MockModel
 * @package Freimaurereis\ServiceClient
 *
 * @property int $id
 * @property string $name  Комментарий к полю
 * @property $street Поле без типа
 * @property void $undefined Поле с неизвестным типом
 * @property float|null $nullable Необязательное поле
 * @property \Freimaurerei\ServiceClient\RelatedModel $related
 * @property null|RelatedModel[] $manyRelatedModels
 * @property array|null $someArray Поле с mixed массивом
 * @property null|int[string] $associativeArray                  Ассоциативный массив
 * @property int[]|null $numericArray
 * @property int[int]|null $nonAssociativeArray Неассоциативный массив
 * @property UnknownModel $unknownModel Поле с несуществующим классом
 * @property CollectionModel $collection Коллекция массивов объектов
 */
class MockModel extends Model
{
    public $explicitField;
}