<?php
/**
 * ActiveRecord builder
 *
 * @author Seiji Amashige <tenjuu99@gmail.com>
 * @package YiiFactoryGirl
 */

namespace YiiFactoryGirl;

/**
 * Builder
 *
 */
class Builder
{
    /**
     * FACTORY_METHOD_SUFFIX
     */
    const FACTORY_METHOD_SUFFIX = 'Factory';

    /**
     * class
     *
     * @var string
     */
    protected $class = '';

    /**
     * factoryData
     *
     * @var YiiFactoryGirl\FactoryData
     */
    protected $factoryData = null;

    /**
     * tableName
     *
     * @var string|null
     */
    private $tableName = null;

    /**
     * reflection
     *
     * @var mixed
     */
    private $reflection = null;

    /**
     * @var $factories
     */
    private static $factories = null;

    /**
     * @var $selfMethods
     */
    private static $selfMethods = array();

    /**
     * @var $callable
     * callable methods cache
     */
    private static $callable = array();

    /**
     * __construct
     *
     * @param string $class
     * @return void
     * @throws FactoryException
     */
    public function __construct($class)
    {
        $this->class = $class;

        try {
            $this->reflection = @new \ReflectionClass($class);
        } catch (FactoryException $e) {
            throw $e;
        } catch (\ReflectionException $e) {
            throw new FactoryException($e->getMessage());
        }
    }

    /**
     * build
     *
     * @param array $attributes
     * @param string|null $alias
     * @param bool $create
     * @return mixed
     */
    public function build($attributes = array(), $alias = null, $create = false)
    {
        // todo 役割ちょっと変わっているのでなんとかしたい
        extract(self::normalizeArguments($this->class, array($attributes, $alias)));
        @list($model, $attributes, $alias) = $args;

        $obj = $this->instantiate();
        $attributes = $this->getFactoryData()->getAttributes($attributes, $alias);
        foreach ($attributes as $key => $value) {
            if ($this->reflection->hasProperty($key)) {
                $property = $this->reflection->getProperty($key);
                $property->setAccessible(true);
                $property->isStatic() ? $property->setValue($value) : $property->setValue($obj, $value);
            } else {
                try {
                    $obj->__set($key, $value);
                } catch(\CException $e) {
                    \Yii::log($e->getMessage(), \CLogger::LEVEL_ERROR);
                    throw new FactoryException(\Yii::t(Factory::LOG_CATEGORY, 'Unknown attribute "{attr} for class {class}.', array(
                        '{attr}' => $key,
                        '{class}' => $this->class
                    )));
                }
            }
        }

        return $create ? $this->create($obj, $relations) : $obj;
    }

    /**
     * create
     *
     * @param \CActiveRecord $obj
     * @param array $relations
     * @return \CActiveRecord
     */
    private function create(\CActiveRecord $obj, $relations = array())
    {
        return Creator::create($obj, $relations);
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
        return $this->reflection->newInstanceArgs();
    }

    /**
     * __callStatic
     *
     * This method emulates factory method
     * if called-method format is '{:ModelName}Factory'.
     *
     * @param string $name method name
     * @param array $args
     * @return mixed
     * @throws YiiFactoryGirl\FactoryException
     */
    public static function __callStatic($name, $args)
    {
        if (!self::isCallable($name)) {
            throw new FactoryException('Call to undefined method ' . __CLASS__ . "::{$name}().");
        }

        if (in_array($name, self::$factories)) {
            $class = str_replace(self::FACTORY_METHOD_SUFFIX, '', $name);
            @list($attr, $alias) = $args;
            if (!$attr) $attr = array();
            //TODO GET RID OF side effect
            $result = Factory::getComponent()->create($class, $attr, $alias);
        } else {
            $result = call_user_func_array(self::$name, $args);
        }

        return $result;
    }

    /**
     * getFactoryData
     *
     * @return FactoryData
     */
    public function getFactoryData()
    {
        if (!$this->factoryData) {
            $file = Factory::getFilePath($this->class.self::FACTORY_METHOD_SUFFIX);
            $this->factoryData = $file ? FactoryData::fromFile($file, 'Factory.php') : new FactoryData($this->class);
        }
        return $this->factoryData;
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
     * isActiveRecord
     *
     * @return bool
     */
    public function isActiveRecord()
    {
        return $this->reflection->isSubclassOf('\CActiveRecord');
    }

    /**
     * isCallable
     *
     * @param string $name method name
     * @return bool
     */
    public static function isCallable($name)
    {
        if (empty(self::$callable)) {
            self::setFactories();
            self::setSelfMethods();
            self::$callable = array_merge(self::$factories, self::$selfMethods);
        }

        if (in_array($name, self::$callable)) {
            return true;
        }

        if (preg_match('/(.*)'.self::FACTORY_METHOD_SUFFIX.'$/', $name, $match)) {
            try {
                $reflection = new \ReflectionClass($match[1]);
                if ($reflection->isSubclassOf('CActiveRecord')) {
                    self::$factories[] = $name;
                    self::$callable[] = $name;
                    return true;
                }
            } catch (\Exception $e) {
                //do nothing
            }
        }

        return false;
    }

    /**
     * setFactories
     *
     * @return void
     */
    private static function setFactories()
    {
        self::$factories = array_map(function($path) {
            return explode('.', $path)[0];
        }, Factory::getComponent()->getFiles(false));
    }

    /**
     * setSelfMethods
     *
     * @return void
     */
    private static function setSelfMethods()
    {
        $reflection = new \ReflectionClass('YiiFactoryGirl\Builder');
        self::$selfMethods = array_map(function($method) {
            return $method->name;
        }, $reflection->getMethods(\ReflectionMethod::IS_PUBLIC));
    }

    /**
     * normalizeArguments
     *
     * @param String $name
     * @param Array $args
     * @return Array
     */
    private static function normalizeArguments($model, Array $arguments)
    {
        $args      = isset($arguments[0]) ? $arguments[0] : array();
        $alias     = isset($arguments[1]) ? $arguments[1] : null;
        $relations = array();

        if ($args) {
            if (@class_exists($model) && @is_subclass_of($model, '\CActiveRecord')) {
                // todo model には activerecord 以外も。。。
                $rels = $model::model()->getMetaData()->relations;
                foreach ($args as $key => $val) {
                    if (isset($rels[$key])) {
                        $relations[$key] = $val;
                        unset($args[$key]);
                    }
                }
            }
            if (isset($args['relations'])) {
                $relations = array_merge($args['relations'], $relations);
                unset($args['relations']);
            }
        }

        return array(
            'args' => array($model, $args, $alias),
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
}
