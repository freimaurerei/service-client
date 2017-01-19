<?php

namespace Freimaurerei\ServiceClient;

use Freimaurerei\ServiceModel\Validators\CastValidator;

class ModelExtendingPropertiesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MockModelExtendingProperties
     */
    protected $model;

    /** @var MockModel */
    protected $parentModel;

    protected $overriddenProperties = ['street'];

    protected function setUp()
    {
        parent::setUp();

        $this->model = new MockModelExtendingProperties();
        $this->parentModel = new MockModel();
    }

    public function testAttributeNames()
    {
        $attributeNames = $this->model->attributeNames();
        $parentAttributeNames = $this->parentModel->attributeNames();

        $this->assertArraySubset($parentAttributeNames, $attributeNames);

        $this->assertEquals(['additionalProperty'], array_values(array_diff($attributeNames, $parentAttributeNames)));
    }

    public function testAttributeOverride()
    {
        $overriddenRules = array_values(array_filter($this->model->rules(), [$this, 'filterRules']));
        $parentRules = array_values(array_filter($this->parentModel->rules(), [$this, 'filterRules']));

        $this->assertNotEquals($parentRules, $overriddenRules);
        $this->assertEquals(
            [
                [
                    'street',
                    CastValidator::CAST_STRING,
                    'allowEmpty' => false,
                ]
            ],
            $overriddenRules
        );
    }

    private function filterRules(array $rule)
    {
        $properties = reset($rule);

        if (is_string($properties)) {
            $properties = array_map('trim', explode(',', $properties));
        }

        foreach ($properties as $property) {
            if (in_array($property, $this->overriddenProperties)) {
                return true;
            }
        }

        return false;
    }
}