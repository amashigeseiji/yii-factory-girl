<?php

namespace YiiFactoryGirl;

/**
 * Class FactoryData
 * Used to represent all properties of a file under the factory base path
 * @package YiiFactoryGirl
 */
class FactoryData
{
    /**
     * className
     *
     * @var string
     */
    private $className;

    /**
     * attributes
     *
     * @var array
     */
    private $attributes;

    /**
     * aliases
     *
     * @var array
     */
    private $aliases;

    /**
     * build
     *
     * @var object
     */
    private $build;

    /**
     * relations
     *
     * @var array
     */
    private $relations;

    /**
     * tableName
     *
     * @var string
     */
    private $tableName;

    /**
     * reflection
     *
     * @var \ReflectionClass
     */
    private $reflection;

    /**
     * instantiateStrategy
     *
     * @var string
     */
    public $instantiateStrategy = 'newInstanceArgs';

    /**
     * __construct
     *
     * @param mixed $className
     * @param array $attributes
     * @param array $aliases
     * @return void
     * @throws YiiFactoryGirl\FactoryException
     */
    public function __construct($className, array $attributes = array(), array $aliases = array()) {
        $this->className = $className;
        try {
            $this->reflection = @new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new FactoryException($e->getMessage());
        }
        $this->attributes = $attributes;
        $this->aliases = $aliases;
    }

    /**
     * getAttributes
     *
     * @param array $args
     * @param mixed $alias
     * @return array
     */
    public function getAttributes($args = array(), $alias = null)
    {
        $attributes = $this->attributes;

        if ($alias !== null) {
            if (!isset($this->aliases[$alias])) {
                throw new FactoryException(\Yii::t(Factory::LOG_CATEGORY, 'Alias "{alias}" not found for class "{class}"', array(
                    '{alias}' => $alias,
                    '{class}' => $this->className,
                )));
            }
            $attributes = array_merge($attributes, $this->aliases[$alias]);
        }

        $attributes = array_merge($attributes, $args);

        foreach ($attributes as $key => $value) {
            $attributes[$key] = is_string($value) ? Sequence::get($value) : $value;
        }

        return $attributes;
    }

    /**
     * build
     *
     * @param array $attributes
     * @param string|null $alias
     * @return YiiFactoryGirl\FactoryData
     */
    public function build($attributes = array(), $alias = null)
    {
        $instance = $this->instantiate();

        extract($this->normalizeAttributes(
            $this->getAttributes($attributes, $alias))
        );

        foreach ($attributes as $key => $value) {
            if ($this->reflection->hasProperty($key)) {
                $property = $this->reflection->getProperty($key);
                $property->setAccessible(true);
                $property->isStatic() ? $property->setValue($value) : $property->setValue($instance, $value);
            } else {
                try {
                    $instance->$key = $value;
                } catch(\CException $e) {
                    \Yii::log($e->getMessage(), \CLogger::LEVEL_ERROR);
                    throw new FactoryException(\Yii::t(Factory::LOG_CATEGORY, 'Unknown attribute "{attr} for class {class}.', array(
                        '{attr}' => $key,
                        '{class}' => $this->className
                    )));
                }
            }
        }

        $this->build = $instance;
        $this->relations = $relations;

        return $this;
    }

    /**
     * instantiate
     *
     * instantiate given class
     *
     * @return object
     */
    private function instantiate()
    {
        return $this->reflection->{$this->instantiateStrategy}(func_get_args());
    }

    /**
     * isActiveRecord
     *
     * @return bool
     */
    public function isActiveRecord()
    {
        return $this->reflection->isSubclassOf('\CActiveRecord');
    }

    /**
     * getTableName
     *
     * @return string|false
     */
    public function getTableName()
    {
        if (!$this->isActiveRecord()) {
            return false;
        }
        if (!$this->tableName) {
            $this->tableName = $this->instantiate()->tableName();
        }
        return $this->tableName;
    }

    /**
     * normalizeAttributes
     *
     * @param Array $args
     * @return Array
     */
    private function normalizeAttributes(Array $attributes)
    {
        $relations = array();

        if ($attributes) {
            if ($this->isActiveRecord()) {
                $metaData = $this->reflection
                    ->getMethod('getMetaData')
                    ->invoke($this->instantiate());
                foreach ($attributes as $key => $val) {
                    if ($metaData->hasRelation($key)) {
                        $relations[$key] = $val;
                        unset($attributes[$key]);
                    }
                }
            }
            if (isset($attributes['relations'])) {
                $relations = array_merge($attributes['relations'], $relations);
                unset($attributes['relations']);
            }
        }

        return array(
            'attributes' => $attributes,
            'relations' => $relations ? self::parseRelationArguments($relations) : array()
        );
    }

    /**
     * parseRelationArguments
     *
     * @param Array $relationsArguments
     * @return Array
     * @throws YiiFactoryGirl\FactoryException
     */
    private static function parseRelationArguments(Array $relationsArguments)
    {
        $relations = array();

        /**
         * following format will be assumed as HAS_MANY relation:
         *
         * FactoryGirl::HogeFactory(array('relations' => array(
         *   ':RelatedObject' => array(
         *       array('Category_id' => 1, 'sortOrder' => 1),
         *       array('Category_id' => 2, 'sortOrder' => 2)
         *   ),
         * )));
         *
         * @param Array $args
         * @return Array
         */
        $hasManyRelation = function(Array $args) {
            $hasMany = array();
            foreach ($args as $key => $val) {
                if (is_int($key) && is_array($val)) {
                    $hasMany[] = $val;
                }
            }
            return $hasMany;
        };

        foreach ($relationsArguments as $key => $value) {
            $model = '';
            $args = array();
            $alias = null;

            if (is_int($key)) { // normal array
                if (is_string($value)) {
                    $model = $value;
                } elseif (is_array($value)) {
                    @list($model, $args, $alias) = $value;
                    if (is_null($args)) {
                        $args = array();
                    }
                } else {
                    throw new FactoryException('Invalid arguments.');
                }
            } else { // hash
                $model = $key;
                is_string($value) ? $alias = $value : $args = $value;
            }

            if (strpos($model, '.')) {
                list($model, $alias) = explode('.', $model);
            }

            if ($hasMany = $hasManyRelation($args)) {
                foreach ($hasMany as $hasManyArgs) {
                    $relations[] = array($model, $hasManyArgs, $alias);
                }
            } else {
                $relations[] = array($model, $args, $alias);
            }
        }

        return $relations;
    }

    /**
     * fromFile
     *
     * @param mixed $path
     * @param mixed $suffix
     * @return YiiFactoryGirl\FactoryData
     */
    public static function fromFile($path, $suffix) {
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $fileName = end($parts);
        if (!substr($fileName, -(strlen($suffix))) === $suffix || !is_file($path)) {
            throw new FactoryException(\Yii::t(Factory::LOG_CATEGORY, '"{file}" does not seem to be factory data file.', array(
                '{file}' => $path
            )));
        }

        // determine class name
        $className = str_replace($suffix, '', $fileName);

        // load actual config
        $config = require $path;
        if (!is_array($config) || !isset($config['attributes']) || !is_array($config['attributes'])) {
            throw new FactoryException(\Yii::t(Factory::LOG_CATEGORY, '"{path}" expected to return config array with "attributes" inside.', array(
                '{path}' => $path,
            )));
        }

        // load attributes and assume the rest of the config is aliases
        $attributes = $config['attributes'];
        unset($config['attributes']);
        $aliases = $config;
        return new self($className, $attributes, $aliases);
    }

    /**
     * __get
     *
     * @return string
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new FactoryException('Unknown property ' . $name . ' in class ' . __CLASS__);
    }
}
