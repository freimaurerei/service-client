<?php

namespace Freimaurerei\ServiceClient;

use Freimaurerei\ServiceModel\ArrayCollection as BaseArrayCollection;
use Freimaurerei\ServiceModel\Exception\ModelException;

abstract class ArrayCollection extends BaseArrayCollection
{
    public function setAttributes($values, $safeOnly = true)
    {
        if (!is_subclass_of($this->getObjectClassName(), Model::class)) {
            throw new ModelException(
                \Yii::t(
                    'arrayCollection',
                    '{class}::getObjectClassName() must return name of a class extending {self} or {model}',
                    [
                        '{class}' => get_class($this),
                        '{self}' => __CLASS__,
                        '{model}' => Model::class,
                    ]
                )
            );
        }

        parent::setAttributes($values, $safeOnly);
    }
}