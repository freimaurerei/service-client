<?php

namespace Freimaurerei\ServiceClient;

use Freimaurerei\ServiceClient\Subspace\MockModel as SubspaceModel;

/**
 * @access protected
 * @property SubspaceModel $model
 */
class SubspaceModelTest extends ModelTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->model = new SubspaceModel();
    }
}