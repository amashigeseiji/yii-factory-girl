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
     * @return mixed
     */
    public function build($attributes = array(), $alias = null)
    {
        return $this->factoryData->build($attributes, $alias)->build;
    }

    /**
     * create
     *
     * @param array $attributes
     * @param string $alias
     * @return \CActiveRecord
     * @throws YiiFactoryGirl\FactoryException
     */
    public function create($attributes = array(), $alias = null)
    {
        if (!$this->isActiveRecord()) {
            throw new FactoryException($this->class.' is not ActiveRecord.');
        }
        $this->factoryData->build($attributes, $alias);
        return Creator::create($this->factoryData->build, $this->factoryData->relations);
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
