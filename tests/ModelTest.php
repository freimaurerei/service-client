<?php

namespace Freimaurerei\ServiceClient;

use Freimaurerei\ServiceModel\Validators\CastValidator;

class ModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MockModel
     */
    protected $model;

    protected function setUp()
    {
        parent::setUp();

        $this->model = new MockModel();
    }

    public function testRelationsGenerator()
    {
        $this->assertEquals(
            [
                'related' => [MockModel::HAS_ONE, RelatedModel::class],
                'manyRelatedModels' => [MockModel::HAS_MANY, RelatedModel::class],
                'collection' => [MockModel::HAS_ONE, CollectionModel::class],
            ],
            $this->model->relations()
        );
    }

    public function testRulesGenerator()
    {
        $clientModelRules = array_values(
            array_filter(
                $this->model->rules(),
                function ($rule) {
                    return $rule[1] !== 'validateSelf' && $rule[1] !== 'safe';
                }
            )
        );

        $this->assertEquals(
            [
                [
                    'id',
                    CastValidator::CAST_INT,
                    'allowEmpty' => false,
                ],
                [
                    'name',
                    CastValidator::CAST_STRING,
                    'allowEmpty' => false,
                ],
                [
                    'nullable',
                    CastValidator::CAST_FLOAT,
                    'allowEmpty' => true,
                ],
                [
                    'manyRelatedModels',
                    CastValidator::CAST_ARRAY,
                    'allowEmpty' => true,
                ],
                [
                    'someArray',
                    CastValidator::CAST_ARRAY,
                    'allowEmpty' => true,
                ],
                [
                    'associativeArray',
                    CastValidator::CAST_ARRAY,
                    'type' => 'int',
                    'isAssociative' => true,
                    'allowEmpty' => true,
                ],
                [
                    'numericArray',
                    CastValidator::CAST_ARRAY,
                    'allowEmpty' => true,
                    'type' => 'int',
                ],
                [
                    'nonAssociativeArray',
                    CastValidator::CAST_ARRAY,
                    'type' => 'int',
                    'isAssociative' => false,
                    'allowEmpty' => true,
                ],
            ],
            $clientModelRules
        );
    }

    public function testIsPropertySet()
    {
        $attribute = 'name';
        $this->assertFalse(isset($this->model->$attribute));

        $value = 'test';
        $this->model->$attribute = $value;

        $this->assertTrue(isset($this->model->$attribute));
        $this->assertSame($value, $this->model->$attribute);
    }

    public function testIsOffsetSet()
    {
        $attribute = 'name';
        $this->assertFalse(isset($this->model[$attribute]));

        $value = 'test';
        $this->model[$attribute] = $value;

        $this->assertTrue(isset($this->model[$attribute]));
        $this->assertSame($value, $this->model[$attribute]);
    }

    public function testIsInvalidPropertySet()
    {
        $attribute = 'invalid';
        $this->assertFalse(isset($this->model->$attribute));
    }

    public function testIsInvalidOffsetSet()
    {
        $attribute = 'invalid';
        $this->assertFalse(isset($this->model[$attribute]));
    }
}