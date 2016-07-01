<?php

use YiiFactoryGirl\Factory;

/**
 * @coversDefaultClass YiiFactoryGirl\Factory
 */
class FactoryTest extends YiiFactoryGirl\UnitTestCase
{
    private static $component = null;

    public static function setUpBeforeClass()
    {
        YiiFactoryGirl\Factory::getComponent()->prepare();
    }

    /**
     * testGetFilesSuccess
     *
     * @return array
     */
    public function testGetFilesSuccess()
    {
        $fileNames = $this->getComponent()->getFiles(false); // not absolute path
        return array(
            'file exists' => array(
                'assert' => 'True',
                'result' => function() {
                    return file_exists($this->getComponent()->getBasePath() . '/BookFactory.php');
                }
            )
        );
    }

    /**
     * testPrepareWithInitSuccess
     *
     * @return array
     */
    public function testPrepareWithInitSuccess()
    {
        return array(
            array(
                'assert' => 'Equals',
                'result' => function() {
                    $this->getComponent()->create('Book');
                    $this->assertGreaterThan(0, Book::model()->count());
                    $this->getComponent(array(), true);
                    return Book::model()->count();
                },
                'expect' => 0
            )
        );
    }

    /**
     * testGetDbConnectionSuccess
     *
     * @return array
     */
    public function testGetDbConnectionSuccess()
    {
        return array(
            array(
                'assert' => 'InstanceOf',
                'result' => function() {
                    return $this->getComponent()->getDbConnection();
                },
                'expect' => 'CDbConnection'
            )
        );
    }

    /**
     * testPrepareSuccess
     *
     * @return array
     */
    public function testPrepareSuccess()
    {
        $create = function() {
            foreach (array('Book', 'Author', 'Publisher', 'HaveNoRelation') as $class) {
                $this->getComponent()->create($class);
            }
        };

        return array(
            array(
                'assert' => 'Equals',
                'result' => function() use ($create) {
                    $create();
                    $this->getComponent()->prepare();
                    return Book::model()->count()
                        + Author::model()->count()
                        + HaveNoRelation::model()->count()
                        + Publisher::model()->count();
                },
                'expect' => 0
            )
        );
    }

    /**
     * testTruncateTablesSuccess
     *
     * @return array
     */
    public function testTruncateTablesSuccess()
    {
        return array(
            array(
                'assert' => 'Equals',
                'result' => function() {
                    $this->getComponent()->create('Book');
                    $this->getComponent()->checkIntegrity(false);
                    $this->getComponent()->truncateTables();
                    $this->getComponent()->checkIntegrity(true);
                    return Book::model()->count();
                },
                'expect' => 0
            )
        );
    }

    /**
     * testTruncateTableFail
     *
     * @return array
     */
    public function testTruncateTableFail()
    {
        return array(
            array(
                'exception' => array('CException', "Table 'NotExist' does not exist"),
                'callback' => function() {
                    $this->getComponent()->truncateTable('NotExist');
                }
            )
        );
    }

    /**
     * testFlushSuccess
     *
     * @return array
     */
    public function testFlushSuccess()
    {
        return array(
            array(
                'assert' => 'Equals',
                'result' => function() {
                    $this->getComponent()->create('Book');
                    $this->getComponent()->create('Author');
                    $this->getComponent()->checkIntegrity(false);
                    $this->getComponent()->flush();
                    $this->getComponent()->checkIntegrity(true);
                    return Book::model()->count()
                        + Author::model()->count();
                },
                'expect' => 0
            ),
        );
    }

    /**
     * testBuildSuccess
     *
     * @return array
     */
    public function testBuildSuccess()
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
     * testBuildFail
     *
     * @return array
     */
    public function testBuildFail()
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
     * testCreateSuccess
     *
     * @return array
     */
    public function testCreateSuccess()
    {
        // HAS MANY
        $hasManyBooksAuthor = $this->getComponent()->AuthorFactory(array('name' => 'Fyodor Dostoevsky', 'relations' => array(
            'Books' => array(
                array('name' => 'Crime and Punishment'),
                array('name' => 'Notes from Underground'),
                array('id' => '45', 'name' => 'The Brothers Karamazov'),
            )
        )));
        $bookId45 = Book::model()->findByPk(45);

        // RECURSIVE
        $recursive = $this->getComponent()->BookFactory(array('relations' => array(
            'Colophon' => array('relations' => array('PublishedBy'))
        )));

        // abbreviated
        $publisher = $this->getComponent()->PublisherFactory(array(
            'name' => 'O\'Reilly',
            'Series' => array(
                'name' => 'Hacks',
                'Books' => array(
                    array('name' => 'Raspberry Pi Hacks'),
                    array('name' => 'HTML5 Hacks'),
                )
            )
        ));

        return array(
            'get instance' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return $this->getComponent()->create('Book');
                },
                'expected' => 'Book'
            ),
            'primary key exists' => array(
                'assert' => 'NotNull',
                'callback' => function() {
                    return $this->getComponent()->create('Book')->id;
                },
            ),
            'record exists' => array(
                'assert' => 'NotNull',
                'callback' => function() {
                    $id = $this->getComponent()->create('Book')->id;
                    return Book::model()->findByPk($id);
                },
            ),

            // composite primary key
            'composite primary key' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return $this->getComponent()->create('Composite', array('pk2' => '{{sequence(:Composite_pk2)}}'));
                },
                'expected' => 'Composite'
            ),
            'composite primary key' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getComponent()->create('Composite', array('pk2' => '{{sequence(:Composite_pk2)}}'))->primaryKey;
                },
                'expected' => function($result) {
                    return Composite::model()->findByPk($result)->primaryKey;
                }
            ),

            // alias relation
            'alias have relation' => array(
                'assert' => 'InstanceOf',
                'result' => function() {
                    return $this->getComponent()->create('Book', array(), 'Karamazov')->Author;
                },
                'expected' => 'Author'
            ),
            'alias relation is correct' => array(
                'assert' => 'Equals',
                'result' => function() {
                    return $this->getComponent()->create('Book', array(), 'Karamazov')->Author->name;
                },
                'expected' => 'Fyodor Dostoevsky'
            ),

            // relation
            'default have no relation' => array(
                'assert' => 'Null',
                'result' => function() {
                    return $this->getComponent()->BookFactory()->Author;
                }
            ),

            // relation: BELONGS_TO
            'BELONGS TO: instanceof Author' => array(
                'assert' => 'InstanceOf',
                'result' => function() {
                    return $this->getComponent()->BookFactory(array('relations' => array(
                        array('Author', array('name' => 'Dazai Osamu'))
                    )))->Author;
                },
                'expected' => 'Author',
            ),
            'BELONGS TO: related record name is correct' => array(
                'assert' => 'Equals',
                'result' => function() {
                    return $this->getComponent()->BookFactory(array('relations' => array(
                        array('Author', array('name' => 'Dazai Osamu'))
                    )))->Author->name;
                },
                'expected' => 'Dazai Osamu',
            ),

            // relation: HAS_MANY
            'HAS MANY: books count is correct' => array(
                'assert' => 'Count',
                'result' => $hasManyBooksAuthor->Books,
                'expected' => 3
            ),
            'HAS MANY: author name is correct' => array(
                'assert' => 'Equals',
                'result' => $hasManyBooksAuthor->name,
                'expected' => 'Fyodor Dostoevsky'
            ),
            'HAS MANY: book name is correct' => array(
                'assert' => 'Equals',
                'result' => $bookId45->name,
                'expected' => 'The Brothers Karamazov'
            ),
            'HAS MANY: book\'s Author id is correct' => array(
                'assert' => 'Equals',
                'result' => $bookId45->Author_id,
                'expected' => $hasManyBooksAuthor->id
            ),

            // relation: HAS_ONE
            'HAS ONE' => array(
                'assert' => 'InstanceOf',
                'result' => function() {
                    return $this->getComponent()->BookFactory(array('relations' => array('Colophon')))->Colophon;
                },
                'expected' => 'Colophon'
            ),

            // relation: recursive
            'RECURSIVE relation' => array(
                'assert' => 'Equals',
                'result' => $recursive->Colophon->Publisher_id,
                'expected' => $recursive->Colophon->PublishedBy->id
            ),

            // alias in relation
            'Alias in relation' => array(
                'assert' => 'Equals',
                'result' => function() {
                    return $this->getComponent()->AuthorFactory(array('relations' => array(
                        'Books.testAlias'
                    )), null, true)->Books[0]->name;
                },
                'expected' => 'inserted by alias'
            ),

            // abbreviate format
            'ABBREVIATED FORMAT: Instance type is correct' => array(
                'assert' => 'InstanceOf',
                'result' => $publisher->Series[0],
                'expected' => 'Series'
            ),
            'ABBREVIATED FORMAT: series name is correct' => array(
                'assert' => 'Equals',
                'result' => $publisher->Series[0]->name,
                'expected' => 'Hacks'
            ),
            'ABBREVIATED FORMAT: series books count is correct' => array(
                'assert' => 'Count',
                'result' => $publisher->Series[0]->Books,
                'expected' => 2
            ),
            'ABBREVIATED FORMAT: series books name is correct' => array(
                'assert' => 'Equals',
                'result' => function() use ($publisher) {
                    return array_map(function($book) {
                        return $book->name;
                    }, $publisher->Series[0]->Books);
                },
                'expected' => array('Raspberry Pi Hacks', 'HTML5 Hacks')
            ),
        );
    }

    /**
     * testCreateFail
     *
     * @return array
     */
    public function testCreateFail()
    {
        return array(
            'Fail if not ActiveRecord' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'BookForm is not ActiveRecord.'),
                'callback'  => function() {
                    $this->getComponent()->create('BookForm');
                }
            ),
            'Fail if primary key and foreign key has same name' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'Primary key and foreign key has same name'),
                'callback'  => function() {
                    $this->getComponent()->checkIntegrity(false);
                    $this->getComponent()->resetTable('SameIdToAuthor');
                    $this->getComponent()->resetTable('Author');
                    $this->getComponent()->checkIntegrity(true);
                    $this->getComponent()->SameIdToAuthorFactory(array('id' => 1, 'relations' => array(
                        'Author' =>  array('id' => 2)
                    )), null, true);
                }
            ),
        );
    }

    /**
     * testIsCallableSuccess
     *
     * todo add isCallable test case
     * @return array
     */
    public function testIsCallableSuccess()
    {
        $assert = function($assert, $name) {
            return array(
                'assert' => $assert,
                'callback' => function() use($name) {
                    $reflection = new ReflectionClass('YiiFactoryGirl\Factory');
                    $property = $reflection->getProperty('_callable');
                    $property->setAccessible(true);
                    $property->setValue(null);
                    return $this->getComponent()->isCallable($name);
                }
            );
        };

        $factoryMethodsCallable = array_map(function($factory) use ($assert) {
            return $assert('True', explode('.', $factory)[0]);
        }, $this->getComponent()->getFiles(false));

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
     * testEmulatedMethodSuccess
     *
     * @return array
     */
    public function testEmulatedMethodSuccess()
    {
        return array(
            'without arguments' => array(
                'assert' => 'InstanceOf',
                'callback' => function() {
                    return $this->getComponent()->HaveNoRelationFactory();
                },
                'expected' => 'HaveNoRelation'
            ),
            'parameter given' => array(
                'assert' => 'Equals',
                'callback' => function() {
                    return $this->getComponent()->HaveNoRelationFactory(array('name' => 'hoge'))->name;
                },
                'expected' => 'hoge'
            ),
        );
    }

    /**
     * testEmulateMethodCallFail
     *
     * @return array
     */
    public function testEmulateMethodCallFail()
    {
        return array(
            array(
                'exception' => array('CException'),
                'callback'  => function() {
                    $this->getComponent()->HogeFugaFactory();
                }
            )
        );
    }


    /* UTILITIES */

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
