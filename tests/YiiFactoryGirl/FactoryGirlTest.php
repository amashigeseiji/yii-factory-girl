<?php

use YiiFactoryGirl\FactoryGirl;

/**
 * @coversDefaultClass YiiFactoryGirl\FactoryGirl
 */
class FactoryGirlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * setUpBeforeClass
     *
     * Migrate
     * @return void
     */
    public static function setUpBeforeClass()
    {
        Yii::app()->migrate->up();
    }

    /**
     * tearDownAfterClass
     *
     * Migrate down
     * @return void
     */
    public static function tearDownAfterClass()
    {
        Yii::app()->migrate->down();
    }

    /**
     * @covers ::isCallable
     */
    public function testIsCallable()
    {
        $reflection = new ReflectionClass('YiiFactoryGirl\Factory');
        foreach ($reflection->getMethods() as $method) {
            if ($method->class === 'CComponent' || $method->class === 'CApplicationComponent' || !$method->isPublic()) {
                $this->assertFalse(FactoryGirl::isCallable($method->name));
            } else {
                $this->assertTrue(FactoryGirl::isCallable($method->name));
            }
        }

        $paths = CFileHelper::findFiles(
            Yii::app()->factorygirl->basePath,
            array('absolutePaths' => false)
        );

        foreach ($paths as $file) {
            $factory = explode('.', $file)[0];
            $this->assertTrue(FactoryGirl::isCallable($factory));
        }

        $this->assertFalse(FactoryGirl::isCallable('unknownMethod'));
        $this->assertFalse(FactoryGirl::isCallable('notExistModelFactory'));
        $this->assertTrue(FactoryGirl::isCallable('TestFactoryGirl__ARFactory'));
    }

    /**
     * @covers ::__callStatic
     */
    public function testEmulatedMethod()
    {
        $this->assertTrue(FactoryGirl::HaveNoRelationFactory() instanceof HaveNoRelation);
        $this->assertEquals('hoge', FactoryGirl::HaveNoRelationFactory(array('name' => 'hoge'))->name);
    }

    /**
     * @covers ::__callStatic
     * @expectedException YiiFactoryGirl\FactoryException
     */
    public function testNotCallable()
    {
        FactoryGirl::HogeFugaFactory();
    }

    /**
     * @covers ::__callStatic
     * @covers ::createRelations
     * @covers ::createRelation
     */
    public function testRelations()
    {
        // Not have relation
        $this->assertNull(FactoryGirl::BookFactory()->Author);

        // BELONGS_TO
        $book1 = FactoryGirl::BookFactory(array('relations' => array(
            array('Author', array('name' => 'Dazai Osamu')), // BelongsTo
        )));
        $this->assertInstanceOf('Author', $book1->Author);
        $this->assertEquals('Dazai Osamu', $book1->Author->name);

        // HAS_MANY
        $author = FactoryGirl::AuthorFactory(array('name' => 'Fyodor Dostoevsky', 'relations' => array(
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
        $book3 = FactoryGirl::BookFactory(array('relations' => array(
            'Colophon'
        )));
        $this->assertInstanceOf('Colophon', $book3->Colophon);

        // recursive
        $book4 = FactoryGirl::BookFactory(array('relations' => array(
            'Colophon' => array('relations' => array('PublishedBy'))
        )));
        $this->assertEquals($book4->Colophon->Publisher_id, $book4->Colophon->PublishedBy->id);

        // relation's alias
        $author2 = FactoryGirl::AuthorFactory(array('relations' => array(
            'Books.testAlias'
        )));
        $this->assertEquals('inserted by alias', $author2->Books[0]->name);
    }

    /**
     * @covers ::createRelation
     * @expectedException YiiFactoryGirl\FactoryException
     * @expectedExceptionMessage Primary key and foreign key has same name
     */
    public function testExceptionIfPrimaryKeyAndForeignKeyHasSameName()
    {
        FactoryGirl::prepare();
        FactoryGirl::SameIdToAuthorFactory(array('id' => 1, 'relations' => array(
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
        $method = new ReflectionMethod('YiiFactoryGirl\FactoryGirl::normalizeArguments');
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
        );
    }
}

/**
 * Mock
 */
class TestFactoryGirl__AR extends CActiveRecord {}
