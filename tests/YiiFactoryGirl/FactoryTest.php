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
        $this->resetInstance();
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
        Yii::app()->setComponent('factorygirl', array('connectionID' => 'migrate'), true);
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
     * @covers ::loadFactoryData
     */
    public function testLoadFactoryData()
    {
        $files = CFileHelper::findFiles(Yii::getPathOfAlias('application.tests.factories'), array('absolutePaths' => false));
        $this->assertCount(count($files), $this->invoke('loadFactoryData'));
        $this->invoke('build', 'Publisher'); // not exists factory file
        $this->assertCount(count($files) + 1, $this->invoke('loadFactoryData'));
        $data = $this->invoke('loadFactoryData');
        $this->assertInstanceOf('YiiFactoryGirl\FactoryData', $data['Publisher']);
        foreach ($files as $file) {
            $name = str_replace($this->getInstance()->factoryFileSuffix . '.php', '', $file);
            $this->assertTrue(array_key_exists($name, $data));
        }
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
     * @covers ::instanciate
     */
    public function testInstanciateSuccessIfActiveRecord()
    {
        $this->invoke('instanciate', 'Book');
    }

    /**
     * @covers ::instanciate
     * @expectedException YiiFactoryGirl\FactoryException
     * @expectedExceptionMessage is not CActiveRecord.
     */
    public function testInstanciateFailIfNotActiveRecord()
    {
        $this->invoke('instanciate', 'CList');
    }

    /**
     * @covers ::instanciate
     * @expectedException YiiFactoryGirl\FactoryException
     * @expectedExceptionMessage There is no
     */
    public function testInstanciateFailIfNotExists()
    {
        $this->invoke('instanciate', 'NotExistClass');
    }

    /**
     * @covers ::getFactoryData
     */
    public function testGetFactoryData()
    {
        $this->assertInstanceOf('YiiFactoryGirl\FactoryData', $this->invoke('getFactoryData', 'Book'));
        $this->assertInstanceOf('YiiFactoryGirl\FactoryData', $this->invoke('getFactoryData', 'Series'));
    }

    /**
     * @covers ::attributes
     */
    public function testAttributes()
    {
        $this->assertEquals(array(), $this->invoke('attributes', 'HaveNoRelation', array(), null));
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
