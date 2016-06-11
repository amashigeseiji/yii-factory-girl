<?php

use YiiFactoryGirl\Factory;

/**
 * @coversDefaultClass YiiFactoryGirl\Factory
 */
class FactoryTest extends PHPUnit_Framework_TestCase
{
    private $reflection = null;

    public function setUp()
    {
        if ($this->getInstance()->connectionID !== 'db') {
            $this->resetInstance();
        }
    }

    /**
     * @covers ::getFiles
     * @covers ::getBasePath
     */
    public function testGetFiles()
    {
        $fileNames = YiiFactoryGirl\Factory::getFiles(false); // not absolute path
        foreach (YiiFactoryGirl\Factory::getFiles() as $path) {
            $this->assertTrue(file_exists($path));
            $this->assertTrue(in_array(end(explode(DIRECTORY_SEPARATOR, $path)), $fileNames));
        }
    }

    /**
     * @covers ::init
     * @covers ::prepare
     */
    public function testPrepareWithInit()
    {
        $this->invoke('create', 'Book');
        $this->assertGreaterThan(0, Book::model()->count());
        $this->resetInstance();
        $this->getInstance();
        $this->assertEquals(0, Book::model()->count());
    }

    /**
     * @covers ::getDbConnection
     */
    public function testGetDbConnection()
    {
        $this->assertInstanceOf('CDbConnection', $this->invoke('getDbConnection'));
    }

    /**
     * @covers ::getDbConnection
     * @expectedException CException
     * @expectedExceptionMessage \YiiFactoryGirl\Factory.connectionID
     */
    public function testGetDbConnectionFail()
    {
        $this->resetInstance();
        Yii::app()->setComponent('factorygirl', array('connectionID' => 'migrate'), true);
        $reflection = new ReflectionObject(Yii::app()->factorygirl);
        $property = $reflection->getProperty('_db');
        $property->setAccessible(true);
        $property->setValue(Yii::app()->factorygirl, null);
        Yii::app()->factorygirl->getDbConnection();
    }

    /**
     * @covers ::prepare
     * @covers ::checkIntegrity
     * @covers ::resetTable
     * @covers ::truncateTable
     */
    public function testPrepare()
    {
        foreach (array('Book', 'Author', 'Publisher', 'HaveNoRelation') as $class) {
            $this->invoke('create', $class);
            $this->invoke('create', $class);
        }
        $this->assertGreaterThan(0, Book::model()->count());
        $this->getInstance()->prepare();
        $this->assertEquals(0, Book::model()->count());
        $this->assertEquals(0, Author::model()->count());
        $this->assertEquals(0, HaveNoRelation::model()->count());
        $this->assertEquals(0, Publisher::model()->count());
    }

    /**
     * @covers ::truncateTable
     * @expectedException CException
     * @expectedExceptionMessage Table 'NotExist' does not exist.
     */
    public function testTruncateTableFailIfTableNotExists()
    {
        $this->invoke('truncateTable', 'NotExist');
    }

    /**
     * @covers ::build
     */
    public function testBuildSuccess()
    {
        $this->assertInstanceOf('Book', $this->invoke('build', 'Book'));
        $this->assertEquals('test name', $this->invoke('build', 'Book', array('name' => 'test name'))->name);
        // property
        $obj = $this->invoke('build', 'HaveNoRelation', array('staticProperty' => 'static property set from reflection property'));
        $this->assertEquals('static property set from reflection property', $obj::$staticProperty);
        $this->assertEquals('public property set from reflection property', $this->invoke('build', 'HaveNoRelation', array('publicProperty' => 'public property set from reflection property'))->publicProperty);
        $this->assertEquals('private property set from reflection property', $this->invoke('build', 'HaveNoRelation', array('publicProperty' => 'private property set from reflection property'))->publicProperty);
    }

    /**
     * @covers ::build
     * @expectedException YiiFactoryGirl\FactoryException
     * @expectedExceptionMessage Unknown attribute
     */
    public function testBuildFail()
    {
        $this->invoke('build', 'HaveNoRelation', array('hoge' => 'hoge'));
    }

    /**
     * @covers ::build
     * @expectedException YiiFactoryGirl\FactoryException
     * @expectedExceptionMessage There is no
     */
    public function testBuildFailIfClassNotExists()
    {
        $this->invoke('build', 'FailClass');
    }

    /**
     * @covers ::create
     * @covers ::build
     */
    public function testCreateSuccess()
    {
        $created = $this->invoke('create', 'Book');
        $this->assertInstanceOf('Book', $created);
        $this->assertNotNull($created->id);
        $this->assertEquals(Book::model()->findByPk($created->id)->id, $created->id);
        // composite primary key
        $composite = $this->invoke('create', 'Composite', array('pk2' => 1));
        $this->assertInstanceOf('Composite', $composite);
        $this->assertInstanceOf('Composite', Composite::model()->findByPk(array($composite->primaryKey)));
    }

    /**
     * @covers ::truncateTables
     */
    public function testTruncateTables()
    {
        $this->invoke('create', 'Book');
        $this->invoke('checkIntegrity', false);
        $this->invoke('truncateTables');
        $this->assertEquals(0, Book::model()->count());
    }

    /**
     * @covers ::flush
     */
    public function testFlush()
    {
        $this->invoke('create', 'Book');
        $this->invoke('create', 'Author');
        $this->invoke('flush');
        $this->assertEquals(0, Book::model()->count());
        $this->assertEquals(0, Author::model()->count());
    }

    /**
     * getInstance
     *
     * @param array $params initialize parameter
     * @return YiiFactoryGirl\Factory
     */
    private function getInstance()
    {
        if (!Yii::app()->hasComponent('factorygirl')) {
            Yii::app()->setComponent('factorygirl', array('class' => 'YiiFactoryGirl\Factory'));
        }
        return Yii::app()->factorygirl;
    }

    /**
     * resetInstance
     *
     * @return void
     */
    private function resetInstance()
    {
        Yii::app()->setComponent('factorygirl', null);
        Yii::app()->setComponent('factorygirl', array('class' => 'YiiFactoryGirl\Factory', 'connectionID' => 'db'));
    }

    /**
     * invoke
     *
     * @param string $name method name
     * @return mixed
     */
    private function invoke($name)
    {
        $this->reflection = new ReflectionObject($this->getInstance());
        $method = $this->reflection->getMethod($name);
        $method->setAccessible(true);
        $args = func_get_args();
        array_shift($args);
        return $method->invokeArgs($this->getInstance(), $args);
    }
}
