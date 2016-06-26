<?php
use YiiFactoryGirl\Builder;

/**
 * BuilderTest
 *
 * @coversDefaultClass YiiFactoryGirl\Builder
 */
class BuilderTest extends YiiFactoryGirl\UnitTestCase
{
    /**
     * @dataProvider constructSuccess
     */
    public function testConstructSuccess($assert, callable $callback, $expected = null)
    {
    }

    /**
     * @dataProvider constructFail
     */
    public function testConstructFail($exception, callable $callback)
    {
    }

    /**
     * - Builder::build can instantiate FormModel
     *
     * @dataProvider buildSuccess
     */
    public function testBuildSuccess($assert, callable $callback, $expected = null)
    {
    }

    /**
     * @dataProvider buildFail
     */
    public function testBuildFail(array $exception, callable $callback)
    {
    }

    /**
     * @dataProvider getFactoryDataSuccess
     */
    public function testGetFactoryDataSuccess()
    {
    }

    /**
     * @dataProvider getTableNameSuccess
     */
    public function testGetTableNameSuccess($assert, callable $callback, $expected = null)
    {
    }

    /**
     * constructSuccess
     *
     * @return array
     */
    public function constructSuccess()
    {
        return array(
            'class name is set' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getProperty(new Builder('Book'), 'class');
                },
                'expected' => 'Book',
            ),
            'FormModel class is allowed' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return (new Builder('BookForm'))->build();
                },
                'expected' => 'CFormModel',
            ),
        );
    }

    /**
     * constructFail
     *
     * @return array
     */
    public function constructFail()
    {
        return array(
            'class not exist' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'Class NotExistClass does not exist'),
                'callback' => function() {
                    new Builder('NotExistClass');
                },
            )
        );
    }

    /**
     * testBuildSuccess arguments
     *
     * define
     *   construct: Builder constructor arguments
     *   tests: test cases. [assert, build, get, expected] key is required.
     * @return array
     */
    public function buildSuccess()
    {
        return array(
            'Build ActiveRecord without arguments' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return (new Builder('Book'))->build();
                },
                'expected' => 'Book',
            ),
            'attribute is given' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return (new Builder('Book'))->build(array('name' => 'sample book'))->name;
                },
                'expected' => 'sample book',
            ),
            'use alias' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return (new Builder('Book'))->build(array(), 'testAlias')->name;
                },
                'expected' => 'inserted by alias',
            ),
            'primary key is not set' => array(
                'assert' => 'Null',
                'callback' => function() {
                    return (new Builder('Book'))->build()->id;
                }
            ),

            // property set
            'static property' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getProperty(
                        (new Builder('HaveNoRelation'))->build(array('staticProperty' => 'static property is set.')),
                        'staticProperty'
                    );
                },
                'expected' => 'static property is set.',
            ),
            'public property' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getProperty(
                        (new Builder('HaveNoRelation'))->build(array('publicProperty' => 'public property is set.')),
                        'publicProperty'
                    );
                },
                'expected' => 'public property is set.',
            ),
            'private property' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getProperty(
                        (new Builder('HaveNoRelation'))->build(array('privateProperty' => 'private property is set.')),
                        'privateProperty'
                    );
                },
                'expected' => 'private property is set.',
            ),

            // FormModel can be build
            'Build form model' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return (new Builder('BookForm'))->build();
                },
                'expected' => 'CFormModel',
            ),
            'Build form model with attributes' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getProperty(
                        (new Builder('BookForm'))->build(array('name' => 'sample book')),
                        'name'
                    );
                },
                'expected' => 'sample book',
            ),
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
            'unknown attribute is given' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'Unknown attribute'),
                'callback' => function() {
                    (new Builder('Book'))->build(array('hoge' => 'fuga'));
                }
            ),
            'alias not exist' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'Alias "aliasNotExist" not found for class "Book"'),
                'callback' => function() {
                    (new Builder('Book'))->build(array(), 'aliasNotExist');
                }
            ),
            'class not exist' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'Class NotExistClass does not exist'),
                'callback' => function() {
                    (new Builder('NotExistClass'))->build();
                }
            )
        );
    }

    /**
     * getFactoryDataSuccess
     *
     * @return array
     */
    public function getFactoryDataSuccess()
    {
        return array(
            'get FactoryData instance when factory file exists' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return (new Builder('Book'))->getFactoryData();
                },
                'expected' => 'YiiFactoryGirl\FactoryData'
            ),
            'FactoryData instance is set className' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return (new Builder('Book'))->getFactoryData()->className;
                },
                'expected' => 'Book'
            ),
            'get FactoryData instance when factory file not exist and model exist' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return (new Builder('Colophon'))->getFactoryData();
                },
                'expected' => 'YiiFactoryGirl\FactoryData'
            ),
            'FactoryData instance is set className even if factory file not exists' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return (new Builder('Colophon'))->getFactoryData()->className;
                },
                'expected' => 'Colophon'
            )
        );
    }

    /**
     * getTableNameSuccess
     *
     * @return array
     */
    public function getTableNameSuccess()
    {
        return array(
            'normal' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return (new Builder('Book'))->getTableName();
                },
                'expected' => 'Book'
            ),
            'different between model name and table name' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return (new Builder('Composite'))->getTableName();
                },
                'expected' => 'CompositePrimaryKeyTable'
            ),
            'table not exist' => array(
                'assert' => 'False',
                'callback' => function() {
                    return (new Builder('BookForm'))->getTableName();
                }
            )
        );
    }


    /* UTILITIES */

    /**
     * invoke
     *
     * @param string $method
     * @param array $construct
     * @param array $args
     * @return mixed
     */
    protected function invoke($method, $instance, $args = array())
    {
        $method = new \ReflectionMethod('YiiFactoryGirl\Builder', $method);
        $method->setAccessible(true);

        return $method->invokeArgs($instance, $args);
    }

    /**
     * getProperty
     *
     * @param mixed $instance
     * @param mixed $get
     * @return void
     */
    protected function getProperty($instance, $get)
    {
        $reflection = new \ReflectionObject($instance);
        if ($reflection->hasProperty($get)) {
            $property = $reflection->getProperty($get);
            $property->setAccessible(true);
            $result = $property->getValue($instance);
        } else {
            $result = $instance->$get;
        }
        return $result;
    }
}
