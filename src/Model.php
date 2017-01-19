<?php

namespace Freimaurerei\ServiceClient;

use DocBlockReader\Reader;
use Freimaurerei\ServiceClient\Helper\Model as Helper;
use Freimaurerei\ServiceModel\Exception\ModelException;
use Freimaurerei\ServiceModel\Validators\ArrayValidator;

abstract class Model extends \Freimaurerei\ServiceModel\Model
{
    const PROPERTY = 'property';

    const PROPERTY_TYPE = 'type';
    const PROPERTY_CASTER = 'caster';
    const PROPERTY_IS_NULLABLE = 'isNullable';
    const PROPERTY_NAMESPACE = 'namespace';

    /**
     * @var bool Экспортировать только установленные значения
     */
    protected $exportOnlySetProperties = true;

    /**
     * @var bool Генерировать правила валидации на основе типов свойств в аннотации модели конечного клиента
     */
    protected $generateRulesFromAnnotations = true;

    /**
     * @var bool Генерировать связи на основе классов свойств в аннотации модели конечного клиента
     */
    protected $generateRelationsFromAnnotations = true;

    protected static $cachePrefix = 'serviceClient';

    /** @var array */
    protected static $modelsBranches = [];

    /** @var Reader[] */
    protected static $docBlockReaders = [];

    /** @var string[] */
    protected static $namespaces = [];

    private static $attributeNames = [];

    private static $annotations = [];

    private static $rules = [];

    private static $relations = [];

    private $_properties = [];

    /** @var bool[] */
    private $_setProperties = [];

    public function __isset($name)
    {
        return isset($this->_properties[$name]);
    }

    public function __unset($name)
    {
        unset($this->_properties[$name]);
        unset($this->_setProperties[$name]);
    }

    public function &__get($name)
    {
        if (isset($this->_setProperties[$name])) {
            return $this->_properties[$name];
        }

        if (!isset($this->getAnnotations()[$name])) {
            throw new ModelException(\Yii::t(
                'yii',
                'Property "{class}.{property}" is not defined.',
                ['{class}' => get_class($this), '{property}' => $name]
            ));
        }

        $this->_properties[$name] = null;

        return $this->_properties[$name];
    }

    public function __set($name, $value)
    {
        if (!isset($this->getAnnotations()[$name])) {
            throw new ModelException(\Yii::t(
                'yii',
                'Property "{class}.{property}" is not defined.',
                ['{class}' => get_class($this), '{property}' => $name]
            ));
        }

        $this->_properties[$name] = $value;
        $this->_setProperties[$name] = true;
    }

    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * @param boolean $exportOnlySetProperties
     */
    public function setExportOnlySetProperties($exportOnlySetProperties)
    {
        $this->exportOnlySetProperties = $exportOnlySetProperties;
    }

    /**
     * @return boolean
     */
    public function getExportOnlySetProperties()
    {
        return $this->exportOnlySetProperties;
    }

    protected static function getDocBlockReader()
    {
        $className = static::class;

        if (!isset(static::$docBlockReaders[$className])) {
            static::$docBlockReaders[$className] = new Reader($className);
        }

        return static::$docBlockReaders[$className];
    }

    /**
     * @return string
     */
    protected static function getNamespace()
    {
        $className = static::class;

        if (!isset(static::$namespaces[$className])) {
            $namespaceAsArray = explode('\\', $className);
            array_pop($namespaceAsArray);
            static::$namespaces[$className] = implode('\\', $namespaceAsArray);
        }
        return static::$namespaces[$className];
    }

    /**
     * @return string[]
     */
    private static function getModelsBranch()
    {
        $className = static::class;

        if (!isset(static::$modelsBranches[$className])) {
            $class = static::class;

            do {
                $models[] = $class;
                $class = get_parent_class($class);
            } while ($class !== __CLASS__);

            static::$modelsBranches[$className] = array_reverse($models);
        }

        return static::$modelsBranches[$className];
    }

    private function _getCacheKey($postfix)
    {
        return static::$cachePrefix . '_' . get_class($this) . "_$postfix";
    }

    private function getAnnotationCacheKey()
    {
        return $this->_getCacheKey('annotations');
    }

    private function getRulesCacheKey()
    {
        return $this->_getCacheKey('rules');
    }

    private function getRelationsCacheKey()
    {
        return $this->_getCacheKey('relations');
    }

    private function getAttributeNamesCacheKey()
    {
        return $this->_getCacheKey('property_names');
    }

    private function getAnnotationsRules()
    {
        $rules = [];

        if ($this->generateRulesFromAnnotations) {
            foreach ($this->getAnnotations() as $propertyName => $annotation) {
                if (isset($annotation[self::PROPERTY_TYPE]) && isset($annotation[self::PROPERTY_CASTER])) {
                    $type = $annotation[self::PROPERTY_TYPE];

                    $rule = [
                        $propertyName,
                        $annotation[self::PROPERTY_CASTER],
                        'allowEmpty' => $annotation[self::PROPERTY_IS_NULLABLE]
                    ];
                    if (Helper::isAnnotationTypeArray($type)) {
                        $type = Helper::getCanonicalType($type, $keysType);

                        if (isset($type)) {
                            $rule['type'] = $type;
                        }

                        if (isset($keysType)) {
                            $rule['isAssociative'] = $keysType && $keysType !== ArrayValidator::TYPE_INT;
                        }
                    }
                    $rules[] = $rule;
                }
            }
        }

        return $rules;
    }

    private function getAnnotationRelations()
    {
        $relations = [];

        if ($this->generateRelationsFromAnnotations) {
            foreach ($this->getAnnotations() as $propertyName => $annotation) {
                if (isset($annotation[self::PROPERTY_TYPE])) {
                    $class = $annotation[self::PROPERTY_TYPE];
                    $isArray = Helper::isAnnotationTypeArray($class);

                    if (!Helper::isPrimitive($class)) {
                        if (isset($annotation[self::PROPERTY_NAMESPACE])) {
                            $class = $annotation[self::PROPERTY_NAMESPACE] . '\\' . $class;
                        }

                        if (class_exists($class) && (
                                is_subclass_of($class, __CLASS__)
                                || is_subclass_of($class, ArrayCollection::class)
                            )
                        ) {
                            $relations[$propertyName] = [
                                $isArray ? self::HAS_MANY : self::HAS_ONE,
                                $class
                            ];
                        }
                    }
                }
            }
        }

        return $relations;
    }

    /**
     * @throws \Freimaurerei\ServiceModel\Exception\ModelException
     * @return array
     */
    private function getAnnotations()
    {
        $className = get_class($this);

        if (!isset(self::$annotations[$className])) {
            $cacheKey = $this->getAnnotationCacheKey();
            $annotations = \Yii::$app->cache->get($cacheKey);

            if ($annotations === false) {
                $annotations = [];

                foreach (static::getModelsBranch() as $model) {
                    /** @var self $model */
                    $classAnnotations = $model::getDocBlockReader()->getParameter(self::PROPERTY);
                    if ($classAnnotations === true) {
                        throw new ModelException('Empty @property is not allowed.');
                    }
                    if (!empty($classAnnotations)) {
                        settype($classAnnotations, 'array');
                        foreach ($classAnnotations as $annotation) {
                            // Разбиваем строку после @property на 2 части
                            list($propertyTypeString, $propertyName) = preg_split('/\s+/', trim($annotation), 3);

                            if (!empty($propertyName) && substr($propertyName, 0, 1) === '$') {
                                // Разбираем мультвариантный тип свойства
                                $propertyTypes = preg_split('/\|(?![^\[]*\])/', $propertyTypeString);

                                $isNullable = false;
                                $propertyType = null;
                                $namespace = null;

                                foreach ($propertyTypes as $type) {
                                    if (strcasecmp($type, 'null') === 0) {
                                        $isNullable = true;
                                    } elseif (!isset($propertyType)) {
                                        $propertyType = trim($type, '\\');
                                    }
                                }

                                if (strpos($propertyType, '\\') === false) {
                                    $namespace = $model::getNamespace();
                                }

                                $annotations[ltrim($propertyName, '$')] = [
                                    self::PROPERTY_CASTER => Helper::getTypeCaster($propertyType),
                                    self::PROPERTY_IS_NULLABLE => $isNullable,
                                    self::PROPERTY_TYPE => $propertyType,
                                    self::PROPERTY_NAMESPACE => $namespace,
                                ];
                            } elseif (!empty($propertyTypeString) && substr($propertyTypeString, 0, 1) === '$') {
                                $annotations[ltrim($propertyTypeString, '$')] = [];
                            }
                        }
                    }
                }

                \Yii::$app->cache->set($cacheKey, $annotations);
            }

            self::$annotations[$className] = $annotations;
        }

        return self::$annotations[$className];
    }

    /**
     * При перегрузке правил валидации не следует забывать подцеплять родительские правила с помощью array_merge()
     *
     * @return array
     */
    public function rules()
    {
        $className = get_class($this);

        if (!isset(self::$rules[$className])) {
            $cacheKey = $this->getRulesCacheKey();
            $rules    = \Yii::$app->cache->get($cacheKey);
            $rules = false;

            if ($rules === false) {
                $rules = array_merge(parent::rules(), $this->getAnnotationsRules());
                \Yii::$app->cache->set($cacheKey, $rules);
            }
            self::$rules[$className] = $rules;
        }

        return self::$rules[$className];
    }

    public function relations()
    {
        $className = get_class($this);

        if (!isset(self::$relations[$className])) {
            $cacheKey = $this->getRelationsCacheKey();
            $relations = \Yii::$app->cache->get($cacheKey);

            if ($relations === false) {
                $relations = array_merge(parent::relations(), $this->getAnnotationRelations());
                \Yii::$app->cache->set($cacheKey, $relations);
            }

            self::$relations[$className] = $relations;
        }

        return self::$relations[$className];
    }

    /**
     * @return string[]
     */
    public function attributeNames()
    {
        $className = get_class($this);

        if (!isset(self::$attributeNames[$className])) {
            $cacheKey = $this->getAttributeNamesCacheKey();
            $attributeNames = \Yii::$app->cache->get($cacheKey);

            if ($attributeNames === false) {
                $attributeNames = array_keys(
                    array_merge(array_flip(parent::attributes()), $this->getAnnotations())
                );
                \Yii::$app->cache->set($cacheKey, $attributeNames);
            }

            self::$attributeNames[$className] = $attributeNames;
        }

        return self::$attributeNames[$className];
    }

    /**
     * @param string[] $names
     * @param array $except
     * @return array
     */
    public function getAttributes($names = null, $except = [])
    {
        if (!isset($names) && $this->exportOnlySetProperties) {
            $names = array_keys(array_merge(array_flip(parent::attributes()), $this->_setProperties));
        }
        return parent::getAttributes($names, $except);
    }
}