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
     * __construct
     *
     * @param string $class
     * @return void
     */
    public function __construct($class)
    {
        $this->class = $class;
        $this->setFactoryData();
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
        $this->factoryData->build($attributes, $alias);
        return $create ? $this->create($this->factoryData->build, $this->factoryData->relations) : $this->factoryData->build;
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
     * setFactoryData
     *
     * @return void
     */
    private function setFactoryData()
    {
        $file = Factory::getFilePath($this->class.Factory::FACTORY_FILE_SUFFIX);
        $this->factoryData = $file ? FactoryData::fromFile($file, 'Factory.php') : new FactoryData($this->class);
    }

    /**
     * getFactoryData
     *
     * @return FactoryData
     */
    public function getFactoryData()
    {
        return $this->factoryData;
    }

    /**
     * getTableName
     *
     * @return string|false
     */
    public function getTableName()
    {
        return $this->getFactoryData()->getTableName();
    }

    /**
     * isActiveRecord
     *
     * @return bool
     */
    public function isActiveRecord()
    {
        return $this->getFactoryData()->isActiveRecord();
    }
}
