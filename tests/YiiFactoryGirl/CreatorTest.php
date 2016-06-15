<?php

use YiiFactoryGirl\Creator;

/**
 * @coversDefaultClass YiiFactoryGirl\Creator
 */
class CreatorTest extends YiiFactoryGirl_Unit_TestCase
{
    /**
     * @covers ::isCallable
     * @covers ::setFactories
     * @covers ::setReflectionMethods
     * @dataProvider isCallableSuccess
     */
    public function testIsCallableSuccess($assert, callable $callback, $expected = null)
    {
        $reflection = new ReflectionClass('YiiFactoryGirl\Creator');
        foreach (array('callable', 'factories', 'reflectionMethods') as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue(null);
        }
    }

    /**
     * @covers ::__callStatic
     * @dataProvider emulatedMethodSuccess
     */
    public function testEmulatedMethodSuccess()
    {
    }

    /**
     * @covers ::__callStatic
     * @expectedException YiiFactoryGirl\FactoryException
     */
    public function testNotCallable()
    {
        Creator::HogeFugaFactory();
    }

    /**
     * @covers ::create
     * @covers ::__callStatic
     * @covers ::createRelations
     * @covers ::createRelation
     * @dataProvider relationsSuccess
     */
    public function testRelationsSuccess()
    {
    }

    /**
     * @covers ::createRelation
     * @expectedException YiiFactoryGirl\FactoryException
     * @expectedExceptionMessage Primary key and foreign key has same name
     */
    public function testExceptionIfPrimaryKeyAndForeignKeyHasSameName()
    {
        $component = YiiFactoryGirl\Factory::getComponent();
        $component->checkIntegrity(false);
        $component->resetTable('SameIdToAuthor');
        $component->resetTable('Author');
        $component->checkIntegrity(true);
        Creator::SameIdToAuthorFactory(array('id' => 1, 'relations' => array(
            'Author' =>  array('id' => 2)
        )));
    }

    /**
     * testNormalizeArguments
     *
     * @covers ::normalizeArguments
     * @covers ::parseRelationArguments
     * @dataProvider arguments
     */
    public function testNormalizeArguments($expected, $model, $args = array(), $alias = null)
    {
        $method = new ReflectionMethod('YiiFactoryGirl\Creator::normalizeArguments');
        $method->setAccessible(true);
        $this->assertEquals($expected, $method->invoke(null, $model, array($args, $alias)));
    }

    /**
     * isCallableSuccess
     *
     * @return array
     */
    public function isCallableSuccess()
    {
        $assert = function($assert, $name) {
            return array(
                'assert' => $assert,
                'callback' => function() use($name) {
                    return YiiFactoryGirl\Creator::isCallable($name);
                }
            );
        };

        $reflection = new ReflectionClass('YiiFactoryGirl\Creator');

        $publicMethodsCallable = array_map(function($method) use ($assert) {
            return $assert('True', $method->name);
        }, $reflection->getMethods(ReflectionMethod::IS_PUBLIC));

        $invisibleMethodsNotCallable = array_map(function($method) use ($assert) {
            return $assert('False', $method->name);
        }, $reflection->getMethods(ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PRIVATE));

        // TODO factory files is used in Builder::build method
        // Is it OK to be callable in Creator::create?
        $factoryMethodsCallable = array_map(function($factory) use ($assert) {
            return $assert('True', explode('.', $factory)[0]);
        }, YiiFactoryGirl\Factory::getFiles(false));

        return array_merge(
            $publicMethodsCallable,
            $invisibleMethodsNotCallable,
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
                    return Creator::HaveNoRelationFactory();
                },
                'expected' => 'HaveNoRelation'
            ),
            array(
                'assert' => 'Equals',
                'callback' => function() {
                    return Creator::HaveNoRelationFactory(array('name' => 'hoge'))->name;
                },
                'expected' => 'hoge'
            ),
        );
    }

    /**
     * relationsSuccess
     *
     * @return array
     */
    public function relationsSuccess()
    {
        // HAS MANY
        $hasManyBooksAuthor = Creator::AuthorFactory(array('name' => 'Fyodor Dostoevsky', 'relations' => array(
            'Books' => array(
                array('name' => 'Crime and Punishment'),
                array('name' => 'Notes from Underground'),
                array('id' => '45', 'name' => 'The Brothers Karamazov'),
            )
        )));
        $bookId45 = Book::model()->findByPk(45);

        // RECURSIVE
        $recursive = Creator::BookFactory(array('relations' => array(
            'Colophon' => array('relations' => array('PublishedBy'))
        )));

        // abbreviated
        $publisher = Creator::PublisherFactory(array(
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
            'default have no relation' => array(
                'assert' => 'Null',
                'result' => function() {
                    return Creator::BookFactory()->Author;
                }
            ),
            'BELONGS TO: instanceof Author' => array(
                'assert' => 'InstanceOf',
                'result' => function() {
                    return Creator::BookFactory(array('relations' => array(
                        array('Author', array('name' => 'Dazai Osamu'))
                    )))->Author;
                },
                'expected' => 'Author',
            ),
            'BELONGS TO: related record name is correct' => array(
                'assert' => 'Equals',
                'result' => function() {
                    return Creator::BookFactory(array('relations' => array(
                        array('Author', array('name' => 'Dazai Osamu'))
                    )))->Author->name;
                },
                'expected' => 'Dazai Osamu',
            ),
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
            'HAS ONE' => array(
                'assert' => 'InstanceOf',
                'result' => function() {
                    return Creator::BookFactory(array('relations' => array('Colophon')))->Colophon;
                },
                'expected' => 'Colophon'
            ),
            'RECURSIVE relation' => array(
                'assert' => 'Equals',
                'result' => $recursive->Colophon->Publisher_id,
                'expected' => $recursive->Colophon->PublishedBy->id
            ),
            'Alias in relation' => array(
                'assert' => 'Equals',
                'result' => function() {
                    return Creator::AuthorFactory(array('relations' => array(
                        'Books.testAlias'
                    )))->Books[0]->name;
                },
                'expected' => 'inserted by alias'
            ),
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
     * dataProvider for testNormalizeArguments
     *
     * @return array
     */
    public function arguments()
    {
        return array(
            'noArguments' => array(
                array('args' => array('User', array(), null), 'relations' => array()),
                'User'
            ),

            'withArgumentsAndAlias' => array(
                array('args' => array('User', array('id' => 1), 'hoge'), 'relations' => array()),
                'User', array('id' => 1), 'hoge'
            ),

            'withRelation' => array(
                array(
                    'args'      => array('User', array('name' => 'hoge'), 'UserAlias'),
                    'relations' => array(
                        array('Identity', array('test' => 'hoge'), 'alias'),
                        array('Hoge', array(), null),
                    )
                ),
                'User',
                array('name' => 'hoge', 'relations' => array(
                    array('Identity', array('test' => 'hoge'), 'alias'),
                    array('Hoge'),
                )),
                'UserAlias'
            ),

            'withRelatin2' => array(
                array(
                    'args'      => array('User', array('name' => 'hoge', 'fuga' => 'tetete'), 'userAlias'),
                    'relations' => array(
                        array('Identity', array('test' => 'hoge'), null),
                        array('Hoge', array(), 'HogeAlias'),
                        array('Fuga', array(), null)
                    )
                ),
                'User',
                array('name' => 'hoge', 'fuga' => 'tetete', 'relations' => array(
                        'Identity' => array('test' => 'hoge'),
                        'Hoge' => 'HogeAlias',
                        'Fuga',
                    )
                ),
                'userAlias'
            ),

            'HAS_MANY' => array(
                array(
                    'args' => array('User', array(), 'alias'),
                    'relations' => array(
                        array('Hoge', array('id' => 1), null),
                        array('Hoge', array('id' => 2), null),
                    )
                ),
                'User',
                array('relations' => array(
                    // This format is to be interpreted as HAS_MANY relation.
                    'Hoge' => array(
                        array('id' => 1),
                        array('id' => 2),
                    ))
                ),
                'alias'
            ),

            'ModelNameAlias' => array(
                array(
                    'args'      => array('User', array(), null),
                    'relations' => array(
                        array('Hoge', array(), 'relationAlias'),
                        array('Fuga', array('id' => 2), 'alias')
                    )
                ),
                'User',
                array('relations' => array(
                    'Hoge.relationAlias',
                    'Fuga.alias' => array('id' => 2)
                )),
            ),

            'abbreviate' => array(
                array('args'      => array('Book', array(), null),
                      'relations' => array(array('Author', array(), null))),
                'Book', array('Author' => array())
            )
        );
    }
}

/**
 * Mock
 */
class TestFactoryGirl__AR extends CActiveRecord {}
