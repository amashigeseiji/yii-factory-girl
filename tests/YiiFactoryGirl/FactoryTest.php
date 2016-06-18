<?php

use YiiFactoryGirl\Factory;

/**
 * @coversDefaultClass YiiFactoryGirl\Factory
 */
class FactoryTest extends YiiFactoryGirl\UnitTestCase
{
    private static $component = null;

    public function setUp()
    {
        if (self::$component && self::$component->connectionID !== 'db') {
            $this->resetComponent();
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
        $this->getComponent(array(), true);
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
        $this->getComponent()->prepare();
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

    /**
     * @covers ::isFactoryMethod
     * @covers ::setFactoryMethods
     * @dataProvider isFactoryMethodSuccess
     */
    public function testIsFactoryMethodSuccess()
    {
        $reflection = new ReflectionClass('YiiFactoryGirl\Factory');
        $property = $reflection->getProperty('_factoryMethods');
        $property->setAccessible(true);
        $property->setValue(null);
    }

    /**
     * @covers ::__call
     * @dataProvider emulatedMethodSuccess
     */
    public function testEmulatedMethodSuccess()
    {
    }

    /**
     * @covers ::__call
     * @expectedException CException
     */
    public function testNotCallable()
    {
        Factory::getComponent()->HogeFugaFactory();
    }

    public function getDbConnectionFail()
    {
        return array(
            array(
                'exception' => array('CException', '\YiiFactoryGirl\Factory.connectionID "migrate" is invalid'),
                'callback'  => function() {
                    $component = $this->getComponent(array('connectionID' => 'migrate'), true);
                    $reflection = new ReflectionObject($component);
                    $property = $reflection->getProperty('_db');
                    $property->setAccessible(true);
                    $property->setValue($component, null);
                    $component->getDbConnection();
                }
            )
        );
    }

    public function truncateTableFail()
    {
        return array(
            array(
                'exception' => array('CException', "Table 'NotExist' does not exist"),
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
                'exception' => array('YiiFactoryGirl\FactoryException', 'Unknown attribute'),
                'callback'  => function() {
                    $this->invoke('build', 'HaveNoRelation', array('hoge' => 'hoge'));
                }
            ),
            array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'Class FailClass does not exist'),
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
     * isFactoryMethodSuccess
     *
     * @return array
     */
    public function isFactoryMethodSuccess()
    {
        $assert = function($assert, $name) {
            return array(
                'assert' => $assert,
                'callback' => function() use($name) {
                    return YiiFactoryGirl\Factory::isFactoryMethod($name);
                }
            );
        };

        $factoryMethodsCallable = array_map(function($factory) use ($assert) {
            return $assert('True', explode('.', $factory)[0]);
        }, YiiFactoryGirl\Factory::getFiles(false));

        return array_merge(
            $factoryMethodsCallable,
            array(
                $assert('False', 'unknownMethod'),
                $assert('False', 'notExistModelFactory'),
                $assert('True', 'TestFactoryGirl__ARFactory'),
                $assert('False', 'NotExistFactory'),
            )
        );
    }

    /**
     * emulatedMethodSuccess
     *
     * @return array
     */
    public function emulatedMethodSuccess()
    {
        return array(
            array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return Factory::getComponent()->HaveNoRelationFactory();
                },
                'expected' => 'HaveNoRelation'
            ),
            array(
                'assert' => 'Equals',
                'callback' => function() {
                    return Factory::getComponent()->HaveNoRelationFactory(array('name' => 'hoge'))->name;
                },
                'expected' => 'hoge'
            ),
        );
    }


    /**
     * resetComponent
     *
     * @return void
     */
    private function resetComponent()
    {
        self::$component = null;
    }

    /**
     * invoke
     *
     * @param string $name method name
     * @return mixed
     */
    protected function invoke($name)
    {
        $reflection = new ReflectionClass('\YiiFactoryGirl\Factory');
        $method = $reflection->getMethod($name);
        $method->setAccessible(true);
        $args = func_get_args();
        array_shift($args);
        return $method->invokeArgs($this->getComponent(), $args);
    }

    /**
     * getComponent
     *
     * @param array $config
     * @return void
     */
    private function getComponent($config = array(), $create = false)
    {
        if (!self::$component || $create) {
            $component = Yii::createComponent(array_merge(
                array(
                    'class' => '\YiiFactoryGirl\Factory',
                    'connectionID' => 'db'
                ),
                $config
            ));
            $component->init();
            return self::$component = $component;
        }

        return self::$component;
    }
}

/**
 * Mock
 */
class TestFactoryGirl__AR extends CActiveRecord {}
