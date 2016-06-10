<?php

use YiiFactoryGirl\FactoryData;

/**
 * @coversDefaultClass YiiFactoryGirl\FactoryData
 */
class FactoryDataTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::fromFile
     * @covers ::__construct
     */
    public function testFromFile()
    {
        $instance = YiiFactoryGirl\FactoryGirl::getInstance();
        $paths = CFileHelper::findFiles($instance->getBasePath());
        foreach ($paths as $path) {
            $this->assertInstanceOf('YiiFactoryGirl\FactoryData', FactoryData::fromFile($path, $instance->factoryFileSuffix . '.php'));
        }
    }

    /**
     * @covers ::getAttributes
     */
    public function testGetAttributesFromFile()
    {
        extract($this->path('Book'));
        $this->assertEquals(array('name' => 'default value'), FactoryData::fromFile($path, $suffix)->getAttributes());
        $this->assertEquals(array('name' => 'inserted by alias'), FactoryData::fromFile($path, $suffix)->getAttributes(array(), 'testAlias'));
        $this->assertEquals(array('id' => 1, 'name' => 'inserted by alias'), FactoryData::fromFile($path, $suffix)->getAttributes(array('id' => 1), 'testAlias'));
    }

    /**
     * @covers ::fromFile
     * @expectedException YiiFactoryGirl\FactoryException
     * @expectedExceptionMessage NotExistFactory.php" does not seem to be factory data file.
     */
    public function testExceptionIfFileNotExist()
    {
        extract($this->path('NotExist'));
        FactoryData::fromFile($path, $suffix);
    }

    /**
     * @covers ::fromFile
     * @expectedException YiiFactoryGirl\FactoryException
     * @expectedExceptionMessage expected to return config array with "attributes" inside
     */
    public function testExceptionIfInvalidFormat()
    {
        extract($this->path('invalid', 'application.tests.invalidfile'));
        FactoryData::fromFile($path, $suffix);
    }

    /**
     * @covers ::__construct
     * @expectedException YiiFactoryGirl\FactoryException
     * @expectedExceptionMessage Unable to call
     */
    public function testConstructorFailIfNotExist()
    {
        new FactoryData('notExist');
    }

    /**
     * @covers ::getAttributes
     * @expectedException YiiFactoryGirl\FactoryException
     * @expectedExceptionMessage Alias "invalidAlias" not found
     */
    public function testExceptionIfInvalidAlias()
    {
        (new FactoryData('Book'))->getAttributes(array(), 'invalidAlias');
    }

    private function path($class, $pathAlias = 'application.tests.factories')
    {
        $instance = YiiFactoryGirl\FactoryGirl::getInstance();
        $suffix = $instance->factoryFileSuffix . '.php';
        return array('path' => \Yii::getPathOfAlias($pathAlias) . DIRECTORY_SEPARATOR . $class . $suffix, 'suffix' => $suffix);
    }
}
