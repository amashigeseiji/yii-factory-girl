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

    public function testGetFiles()
    {
        $fileNames = YiiFactoryGirl\Factory::getFiles(false); // not absolute path
        foreach (YiiFactoryGirl\Factory::getFiles() as $path) {
            $this->assertTrue(file_exists($path));
            $this->assertTrue(in_array(end(explode(DIRECTORY_SEPARATOR, $path)), $fileNames));
        }
    }

    public function testPrepareWithInit()
    {
        $this->invoke('create', 'Book');
        $this->assertGreaterThan(0, Book::model()->count());
        $this->getComponent(array(), true);
        $this->assertEquals(0, Book::model()->count());
    }

    public function testGetDbConnection()
    {
        $this->assertInstanceOf('CDbConnection', $this->invoke('getDbConnection'));
    }

    /**
     * @dataProvider getDbConnectionFail
     */
    public function testGetDbConnectionFail($exception, callable $callback)
    {
    }

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
     * @dataProvider truncateTableFail
     */
    public function testTruncateTableFailIfTableNotExists($exception, callable $callback)
    {
    }

    /**
     * @dataProvider buildSuccess
     */
    public function testBuildSuccess($assert, callable $callback, $expected = null)
    {
    }

    /**
     * @dataProvider buildFail
     */
    public function testBuildFail($exception, callable $callback)
    {
    }

    /**
     * @dataProvider createSuccess
     */
    public function testCreateSuccess($assert, callable $callback, $expected = null)
    {
    }

    public function testTruncateTables()
    {
        $this->invoke('create', 'Book');
        $this->invoke('checkIntegrity', false);
        $this->invoke('truncateTables');
        $this->assertEquals(0, Book::model()->count());
    }

    public function testFlush()
    {
        $this->invoke('create', 'Book');
        $this->invoke('create', 'Author');
        $this->invoke('flush');
        $this->assertEquals(0, Book::model()->count());
        $this->assertEquals(0, Author::model()->count());
    }

    /**
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
     * @dataProvider emulatedMethodSuccess
     */
    public function testEmulatedMethodSuccess()
    {
    }

    /**
     * @expectedException CException
     */
    public function testNotCallable()
    {
        Factory::getComponent()->HogeFugaFactory();
    }

    /**
     * getDbConnectionFail
     *
     * @return array
     */
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

    /**
     * truncateTableFail
     *
     * @return array
     */
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

    /**
     * buildSuccess
     *
     * @return array
     */
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

    /**
     * buildFail
     *
     * @return array
     */
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

    /**
     * createSuccess
     *
     * @return array
     */
    public function createSuccess()
    {
        return array(
            'get instance' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return Factory::getComponent()->create('Book');
                },
                'expected' => 'Book'
            ),
            'primary key exists' => array(
                'assert' => 'NotNull',
                'callback' => function() {
                    return Factory::getComponent()->create('Book')->id;
                },
            ),
            'record exists' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return Factory::getComponent()->create('Book')->id;
                },
                'expected' => function($result) {
                    return Book::model()->findByPk($result)->id;
                }
            ),

            // composite primary key
            'composite primary key' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return Factory::getComponent()->create('Composite', array('pk2' => '{{sequence(:Composite_pk2)}}'));
                },
                'expected' => 'Composite'
            ),
            'composite primary key' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return Factory::getComponent()->create('Composite', array('pk2' => '{{sequence(:Composite_pk2)}}'))->primaryKey;
                },
                'expected' => function($result) {
                    return Composite::model()->findByPk($result)->primaryKey;
                }
            ),

            // alias relation
            'alias have relation' => array(
                'assert' => 'InstanceOf',
                'result' => function() {
                    return Factory::getComponent()->create('Book', array(), 'Karamazov')->Author;
                },
                'expected' => 'Author'
            ),
            'alias relation is correct' => array(
                'assert' => 'Equals',
                'result' => function() {
                    return Factory::getComponent()->create('Book', array(), 'Karamazov')->Author->name;
                },
                'expected' => 'Fyodor Dostoevsky'
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
