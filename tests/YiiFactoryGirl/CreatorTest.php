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
     */
    public function testIsCallable()
    {
        $reflection = new ReflectionClass('YiiFactoryGirl\Creator');
        foreach ($reflection->getMethods() as $method) {
            if (!$method->isPublic()) {
                $this->assertFalse(Creator::isCallable($method->name));
            } else {
                $this->assertTrue(Creator::isCallable($method->name));
            }
        }

        $paths = CFileHelper::findFiles(
            YiiFactoryGirl\Factory::getBasePath(),
            array('absolutePaths' => false)
        );

        foreach ($paths as $file) {
            $factory = explode('.', $file)[0];
            $this->assertTrue(Creator::isCallable($factory));
        }

        $this->assertFalse(Creator::isCallable('unknownMethod'));
        $this->assertFalse(Creator::isCallable('notExistModelFactory'));
        $this->assertTrue(Creator::isCallable('TestFactoryGirl__ARFactory'));
        $this->assertFalse(Creator::isCallable('NotExistsFactory'));
    }

    /**
     * @covers ::__callStatic
     */
    public function testEmulatedMethod()
    {
        $this->assertTrue(Creator::HaveNoRelationFactory() instanceof HaveNoRelation);
        $this->assertEquals('hoge', Creator::HaveNoRelationFactory(array('name' => 'hoge'))->name);
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
     */
    public function testRelations()
    {
        // Not have relation
        $this->assertNull(Creator::BookFactory()->Author);

        // BELONGS_TO
        $book1 = Creator::BookFactory(array('relations' => array(
            array('Author', array('name' => 'Dazai Osamu')), // BelongsTo
        )));
        $this->assertInstanceOf('Author', $book1->Author);
        $this->assertEquals('Dazai Osamu', $book1->Author->name);

        // HAS_MANY
        $author = Creator::AuthorFactory(array('name' => 'Fyodor Dostoevsky', 'relations' => array(
            'Books' => array(
                array('name' => 'Crime and Punishment'),
                array('name' => 'Notes from Underground'),
                array('id' => '45', 'name' => 'The Brothers Karamazov'),
            )
        )));
        $this->assertCount(3, $author->Books);
        $book2 = Book::model()->findByPk(45);
        $this->assertEquals($author->id, $book2->Author_id);
        $this->assertEquals('Fyodor Dostoevsky', $book2->Author->name);
        $this->assertEquals('The Brothers Karamazov', $book2->name);

        // HAS_ONE
        $book3 = Creator::BookFactory(array('relations' => array(
            'Colophon'
        )));
        $this->assertInstanceOf('Colophon', $book3->Colophon);

        // recursive
        $book4 = Creator::BookFactory(array('relations' => array(
            'Colophon' => array('relations' => array('PublishedBy'))
        )));
        $this->assertEquals($book4->Colophon->Publisher_id, $book4->Colophon->PublishedBy->id);

        // relation's alias
        $author2 = Creator::AuthorFactory(array('relations' => array(
            'Books.testAlias'
        )));
        $this->assertEquals('inserted by alias', $author2->Books[0]->name);

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
        $this->assertInstanceOf('Series', $publisher->Series[0]);
        $this->assertEquals('Hacks', $publisher->Series[0]->name);
        $this->assertCount(2, $publisher->Series[0]->Books);
        $bookNames = array();
        foreach ($publisher->Series[0]->Books as $book) {
            $bookNames[] = $book->name;
        }
        $this->assertContains('Raspberry Pi Hacks', $bookNames);
        $this->assertContains('HTML5 Hacks', $bookNames);
    }

    /**
     * @covers ::createRelation
     * @expectedException YiiFactoryGirl\FactoryException
     * @expectedExceptionMessage Primary key and foreign key has same name
     */
    public function testExceptionIfPrimaryKeyAndForeignKeyHasSameName()
    {
        YiiFactoryGirl\Factory::getComponent()->prepare();
        Creator::SameIdToAuthorFactory(array('id' => 10, 'relations' => array(
            'Author' =>  array('id' => 20)
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
