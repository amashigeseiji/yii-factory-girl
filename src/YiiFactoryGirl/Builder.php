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
     * FACTORY_FILE_SUFFIX
     */
    const FACTORY_FILE_SUFFIX = 'Factory';

    /**
     * class
     *
     * @var string
     */
    protected $class = '';

    /**
     * allowed
     *
     * allowed instantiate type
     * default: \CActiveRecord
     *
     * @var string
     */
    protected $allowed = '\CActiveRecord';

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
     * __construct
     *
     * @param string $class
     * @param string $allowed allowed instantiate type
     * @return void
     */
    public function __construct($class, $allowed = null)
    {
        $this->class = $class;
        if ($allowed) {
            $this->allowed = $allowed;
        }
    }

    /**
     * build
     *
     * @param array $attributes
     * @param string|null $alias
     * @param bool $create
     * @return \CActiveRecord
     */
    public function build($attributes = array(), $alias = null, $create = false)
    {
        $obj = $this->instantiate();
        $reflection = new \ReflectionObject($obj);
        $attributes = $this->getFactoryData()->getAttributes($attributes, $alias);
        foreach ($attributes as $key => $value) {
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);
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

        return $create ? $this->create($obj) : $obj;
    }

    /**
     * create
     *
     * @param \CActiveRecord $obj
     * @return \CActiveRecord
     */
    private function create(\CActiveRecord $obj)
    {
        return Creator::create($obj);
    }

    /**
     * instantiate
     *
     * instantiate ActiveRecord
     *
     * @return \CActiveRecord
     * @throws FactoryException
     */
    private function instantiate()
    {
        try {
            $obj = new $this->class;
            if (!$obj instanceof $this->allowed) {
                throw new FactoryException("{$this->class} is not {$this->allowed}.");
            }
        } catch (FactoryException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new FactoryException(\Yii::t(Factory::LOG_CATEGORY, 'There is no {class} class loaded.', array(
                '{class}' => $this->class,
            )));
        }

        return $obj;
    }

    /**
     * getFactoryData
     *
     * @return FactoryData
     */
    public function getFactoryData()
    {
        if (!$this->factoryData) {
            $file = Factory::getFilePath($this->class.self::FACTORY_FILE_SUFFIX);
            $this->factoryData = $file ? FactoryData::fromFile($file, 'Factory.php') : new FactoryData($this->class);
        }
        return $this->factoryData;
    }

    /**
     * getTableName
     *
     * @return string
     */
    public function getTableName()
    {
        if (!$this->tableName && ($this->allowed === 'CActiveRecord' || $this->allowed === '\CActiveRecord')) {
            $instance = $this->instantiate();
            if (is_callable(array($instance, 'tableName'))) {
                $this->tableName = $instance->tableName();
            }
        }
        return $this->tableName;
    }
}
