<?php

namespace Freimaurerei\ServiceClient\Subspace;

use Freimaurerei\ServiceClient\MockModel as Model;

class MockModel extends Model
{
    public function init()
    {
        parent::init();

        $this->id = 1;
    }
}