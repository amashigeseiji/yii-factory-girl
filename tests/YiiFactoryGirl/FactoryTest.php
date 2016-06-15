<?php

use YiiFactoryGirl\Factory;

/**
 * @coversDefaultClass YiiFactoryGirl\Factory
 */
class FactoryTest extends YiiFactoryGirl_Unit_TestCase
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
     * @dataProvider getDbConnectionFail
     */
    public function testGetDbConnectionFail($exception, callable $callback)
    {
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
     * @dataProvider truncateTableFail
     */
    public function testTruncateTableFailIfTableNotExists($exception, callable $callback)
    {
    }

    /**
     * @covers ::build
     * @dataProvider buildSuccess
     */
    public function testBuildSuccess($assert, callable $callback, $expected = null)
    {
    }

    /**
     * @covers ::build
     * @dataProvider buildFail
     */
    public function testBuildFail($exception, callable $callback)
    {
    }

    /**
     * @covers ::create
     * @dataProvider createSuccess
     */
    public function testCreateSuccess($assert, callable $callback, $expected = null)
    {
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

    public function getDbConnectionFail()
    {
        return array(
            array(
                'exception' => array('CException', '/\\\YiiFactoryGirl\\\Factory.connectionID "migrate" is invalid/'),
                'callback'  => function() {
                    $this->resetInstance();
                    Yii::app()->setComponent('factorygirl', array('connectionID' => 'migrate'), true);
                    $reflection = new ReflectionObject(Yii::app()->factorygirl);
                    $property = $reflection->getProperty('_db');
                    $property->setAccessible(true);
                    $property->setValue(Yii::app()->factorygirl, null);
                    Yii::app()->factorygirl->getDbConnection();
                }
            )
        );
    }

    public function truncateTableFail()
    {
        return array(
            array(
                'exception' => array('CException', "/Table 'NotExist' does not exist/"),
                'callback' => function() {
                    $this->invoke('truncateTable', 'NotExist');
                }
            )
        );
    }

    public function buildSuccess()
    {
        return array(
            array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return $this->invoke('build', 'Book');
                },
                'expected' => 'Book'
            ),
            array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->invoke('build', 'Book', array('name' => 'test name'))->name;
                },
                'expected' => 'test name'
            ),
            array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->invoke('build', 'Book', array(), 'testAlias')->name;
                },
                'expected' => 'inserted by alias'
            )
        );
    }

    public function buildFail()
    {
        return array(
            array(
                'exception' => array('YiiFactoryGirl\FactoryException', '/Unknown attribute/'),
                'callback'  => function() {
                    $this->invoke('build', 'HaveNoRelation', array('hoge' => 'hoge'));
                }
            ),
            array(
                'exception' => array('YiiFactoryGirl\FactoryException', '/There is no/'),
                'callback'  => function() {
                    $this->invoke('build', 'FailClass');
                }
            ),
        );
    }

    public function createSuccess()
    {
        return array(
            array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return $this->invoke('create', 'Book');
                },
                'expected' => 'Book'
            ),
            array(
                'assert' => 'NotNull',
                'callback' => function() {
                    return $this->invoke('create', 'Book')->id;
                },
            ),
            array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->invoke('create', 'Book')->id;
                },
                'expected' => function($result) {
                    return Book::model()->findByPk($result)->id;
                }
            ),
            'composite' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return $this->invoke('create', 'Composite', array('pk2' => '{{sequence(:Composite_pk2)}}'));
                },
                'expected' => 'Composite'
            ),
            'composite2' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->invoke('create', 'Composite', array('pk2' => '{{sequence(:Composite_pk2)}}'))->primaryKey;
                },
                'expected' => function($result) {
                    return Composite::model()->findByPk($result)->primaryKey;
                }
            ),
        );
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
    protected function invoke($name)
    {
        if (!$this->reflection) {
            $this->reflection = new ReflectionObject($this->getInstance());
        }
        $method = $this->reflection->getMethod($name);
        $method->setAccessible(true);
        $args = func_get_args();
        array_shift($args);
        return $method->invokeArgs($this->getInstance(), $args);
    }
}
