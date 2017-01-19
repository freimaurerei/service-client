<?php

namespace Freimaurerei\ServiceClient;

/**
 * Class CollectionModel
 * @package Freimaurerei\ServiceClient
 */
class CollectionModel extends ArrayCollection
{
    protected function getObjectClassName()
    {
        return RelatedModel::class;
    }
}