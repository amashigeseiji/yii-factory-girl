<?php
use YiiFactoryGirl\Builder;

/**
 * BuilderTest
 *
 * @coversDefaultClass YiiFactoryGirl\Builder
 */
class BuilderTest extends YiiFactoryGirl\UnitTestCase
{
    protected $subject = 'YiiFactoryGirl\Builder';

    /**
     * @covers ::__construct
     * @dataProvider constructSuccess
     */
    public function testConstructSuccess($assert, callable $callback, $expected = null)
    {
    }

    /**
     * - Builder::build can instantiate FormModel
     * - Builder::build can create record
     *
     * @covers ::build
     * @covers ::create
     * @dataProvider buildSuccess
     */
    public function testBuildSuccess($assert, callable $callback, $expected = null)
    {
    }

    /**
     * @covers ::build
     * @dataProvider buildFail
     */
    public function testBuildFail(array $exception, callable $callback)
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
     * @covers ::instantiate
     * @dataProvider instantiateSuccess
     */
    public function testInstantiateSuccess($assert, callable $callback, $expected = null)
    {
    }

    /**
     * @covers ::instantiate
     * @dataProvider instantiateFail
     */
    public function testInstantiateFail($exception, callable $callback)
    {
    }

    /**
     * @covers ::getFactoryData
     * @dataProvider getFactoryDataSuccess
     */
    public function testGetFactoryDataSuccess()
    {
    }

    /**
     * @covers ::getTableName
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
                    return $this->getProperty($this->subject(array('Book')), 'class');
                },
                'expected' => 'Book',
            ),
            'CActiveRecord is default' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getProperty($this->subject(array('Book')), 'allowed');
                },
                'expected' => '\CActiveRecord',
            ),
            'BookForm class' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getProperty($this->subject(array('BookForm', 'CFormModel')), 'class');
                },
                'expected' => 'BookForm',
            ),
            'CFormModel is allowed' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getProperty($this->subject(array('BookForm', 'CFormModel')), 'allowed');
                },
                'expected' => 'CFormModel',
            ),
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
                    return $this->invoke('build', array('Book'));
                },
                'expected' => 'Book',
            ),
            'attribute is given' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getProperty(
                        $this->invoke('build', array('Book'), array(array('name' => 'sample book'))),
                        'name'
                    );
                },
                'expected' => 'sample book',
            ),
            'use alias' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getProperty(
                        $this->invoke('build', array('Book'), array(array(), 'testAlias')),
                        'name'
                    );
                },
                'expected' => 'inserted by alias',
            ),

            // property set
            'static property' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getProperty(
                        $this->invoke('build', array('HaveNoRelation'), array(array('staticProperty' => 'static property is set.'))),
                        'staticProperty'
                    );
                },
                'expected' => 'static property is set.',
            ),
            'public property' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getProperty(
                        $this->invoke('build', array('HaveNoRelation'), array(array('publicProperty' => 'public property is set.'))),
                        'publicProperty'
                    );
                },
                'expected' => 'public property is set.',
            ),
            'private property' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getProperty(
                        $this->invoke('build', array('HaveNoRelation'), array(array('privateProperty' => 'private property is set.'))),
                        'privateProperty'
                    );
                },
                'expected' => 'private property is set.',
            ),

            // FormModel can be build
            'Build form model' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return $this->invoke('build', array('BookForm', 'CFormModel'));
                },
                'expected' => 'CFormModel',
            ),
            'Build form model with attributes' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getProperty(
                        $this->invoke('build', array('BookForm', 'CFormModel'), array(array('name' => 'sample book'))),
                        'name'
                    );
                },
                'expected' => 'sample book',
            ),

            // create
            'create' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return $this->invoke('build', array('Book'), array(array(), null, true));
                },
                'expected' => 'Book',
            ),
            'created id does exist' => array(
                'assert' => 'NotNull',
                'callback' => function() {
                    return $this->getProperty(
                        $this->invoke('build', array('Book'), array(array(), null, true)),
                        'id'
                    );
                },
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
            'UnknownAttribute' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'Unknown attribute'),
                'callback' => function() {
                    (new Builder('Book'))->build(array('hoge' => 'fuga'));
                }
            ),
            'AliasNotExist' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'Alias "aliasNotExist" not found for class "Book"'),
                'callback' => function() {
                    (new Builder('Book'))->build(array(), 'aliasNotExist');
                }
            ),
            'ClassNotExist' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'There is no'),
                'callback' => function() {
                    (new Builder('NotExistClass'))->build();
                }
            )
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
            array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return $this->invoke('create', array(''), array(new Book));
                },
                'expected' => 'Book',
            ),
            array(
                'assert' => 'NotNull',
                'callback' => function() {
                    return $this->invoke('create', array(''), array(new Book))->id;
                },
            ),
            array(
                'assert' => 'NotNull',
                'callback' => function() {
                    return (new Builder('Composite'))->build(array('pk2' => YiiFactoryGirl\Sequence::get('{{sequence}}')), null, true)->primaryKey;
                },
                'expected' => function($result) {
                    return Composite::model()->findByPk($result)->primaryKey;
                }
            ),
        );
    }

    /**
     * instantiateSuccess
     *
     * @return array
     */
    public function instantiateSuccess()
    {
        return array(
            'ActiveRecord' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return $this->invoke('instantiate', array('Book'));
                },
                'expected' => 'CActiveRecord',
            ),
            'FormModel' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return $this->invoke('instantiate', array('BookForm', 'CFormModel'));
                },
                'expected' => 'CFormModel',
            )
        );
    }

    /**
     * instantiateFail
     *
     * @return array
     */
    public function instantiateFail()
    {
        return array(
            'notAllowed1' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', '\CList is not \CActiveRecord.'),
                'callback' => function() {
                    $this->invoke('instantiate', array('\CList'));
                },
            ),
            'notAllowed2' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'Book is not CFormModel.'),
                'callback' => function() {
                    $this->invoke('instantiate', array('Book', 'CFormModel'));
                },
            ),
            'notExist' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'There is no NotExistClass class loaded.'),
                'callback' => function() {
                    $this->invoke('instantiate', array('NotExistClass', 'CFormModel'));
                },
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
                'assert' => 'Null',
                'callback' => function() {
                    return (new Builder('BookForm', 'CFormModel'))->getTableName();
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
    protected function invoke($method, $construct = array(), $args = array())
    {
        $method = new \ReflectionMethod($this->subject, $method);
        $method->setAccessible(true);

        return $method->invokeArgs($this->subject($construct), $args);
    }

    /**
     * getSubject
     *
     * @param mixed $construct
     * @return object
     */
    protected function subject($construct = array())
    {
        if (!is_array($construct)) {
            $construct = array($construct);
        }
        return (new \ReflectionClass($this->subject))
            ->newInstanceArgs($construct);
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
