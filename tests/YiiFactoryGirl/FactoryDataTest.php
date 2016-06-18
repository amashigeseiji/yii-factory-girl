<?php

use YiiFactoryGirl\FactoryData;

/**
 * @coversDefaultClass YiiFactoryGirl\FactoryData
 */
class FactoryDataTest extends PHPUnit_Framework_TestCase
{
    /**
     */
    public function testFromFile()
    {
        $instance = YiiFactoryGirl\Factory::getComponent();
        $paths = CFileHelper::findFiles($instance->getBasePath());
        foreach ($paths as $path) {
            $this->assertInstanceOf('YiiFactoryGirl\FactoryData', FactoryData::fromFile($path, $instance->factoryFileSuffix . '.php'));
        }
    }

    /**
     */
    public function testGetAttributesFromFile()
    {
        extract($this->path('Book'));
        $this->assertEquals(array('name' => 'default value'), FactoryData::fromFile($path, $suffix)->getAttributes());
        $this->assertEquals(array('name' => 'inserted by alias'), FactoryData::fromFile($path, $suffix)->getAttributes(array(), 'testAlias'));
        $this->assertEquals(array('id' => 1, 'name' => 'inserted by alias'), FactoryData::fromFile($path, $suffix)->getAttributes(array('id' => 1), 'testAlias'));
    }

    /**
     * @expectedException YiiFactoryGirl\FactoryException
     * @expectedExceptionMessage NotExistFactory.php" does not seem to be factory data file.
     */
    public function testExceptionIfFileNotExist()
    {
        extract($this->path('NotExist'));
        FactoryData::fromFile($path, $suffix);
    }

    /**
     * @expectedException YiiFactoryGirl\FactoryException
     * @expectedExceptionMessage expected to return config array with "attributes" inside
     */
    public function testExceptionIfInvalidFormat()
    {
        extract($this->path('invalid', 'application.tests.invalidfile'));
        FactoryData::fromFile($path, $suffix);
    }

    /**
     * @expectedException YiiFactoryGirl\FactoryException
     * @expectedExceptionMessage Alias "invalidAlias" not found
     */
    public function testExceptionIfInvalidAlias()
    {
        (new FactoryData('Book'))->getAttributes(array(), 'invalidAlias');
    }

    private function path($class, $pathAlias = 'application.tests.factories')
    {
        $instance = YiiFactoryGirl\Factory::getComponent();
        $suffix = $instance->factoryFileSuffix . '.php';
        return array('path' => \Yii::getPathOfAlias($pathAlias) . DIRECTORY_SEPARATOR . $class . $suffix, 'suffix' => $suffix);
    }
}
