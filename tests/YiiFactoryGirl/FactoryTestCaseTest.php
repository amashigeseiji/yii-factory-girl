<?php

use YiiFactoryGirl\FactoryTestCase;

/**
 * @coversDefaultClass YiiFactoryGirl\FactoryTestCase
 */
class FactoryTestCaseTest extends FactoryTestCase
{
    protected $factories = array(
        'noRelation1'    => 'HaveNoRelation',
        'noRelation2'    => array('HaveNoRelation', array('name' => 'hoge')),
        'author1'    => 'Author',
        'book1'    => array('Book', array('Author' => 'author1')),
        'notExist' => array('NotExist'),
        'author2'    => array('Author', array(
            'relations' => array('Books')
        )),
        'publisher' => array('Publisher', array(
            'name' => 'Iwanami shoten publishers',
            'relations' => array(
                'Series' => array(
                    array(
                        'name' => 'Iwanami library',
                        'relations' => array('Books' => array(
                            array('name' => 'The letters of Vincent Van Gogh'),
                            array('name' => 'The Critic of Pure Reason'),
                            array('name' => 'Discourse on Method')
                        ))
                    ),
                    array(
                        'name' => 'Iwanami science library',
                        'relations' => array('Books' => array(
                            array('name' => 'What is the "Elements" by Euclid?'),
                            array('name' => 'Why does Human draw a picture?'),
                        ))
                    ),
                )
            )
        )),
        'publisher2' => array('Publisher', array(
            'name' => 'Iwanami shoten publishers',
            'Series' => array(
                array(
                    'name' => 'Iwanami library',
                    'Books' => array(
                        array('name' => 'The letters of Vincent Van Gogh'),
                        array('name' => 'The Critic of Pure Reason'),
                        array('name' => 'Discourse on Method')
                    )
                ),
                array(
                    'name' => 'Iwanami science library',
                    'Books' => array(
                        array('name' => 'What is the "Elements" by Euclid?'),
                        array('name' => 'Why does Human draw a picture?'),
                    )
                ),
            )
        )
    )
    );

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
     * @covers ::__get
     */
    public function testFactoryTestCase()
    {
        $this->assertNotNull(HaveNoRelation::model()->findByPk($this->noRelation1->id));
        $this->assertNotNull(HaveNoRelation::model()->findByPk($this->noRelation2->id));
        $this->assertEquals('hoge', $this->noRelation2->name);
        // Is ActiveRecord cached in FactoryTestCase::$repository?
        $this->assertEquals($this->noRelation1->id, $this->noRelation1->id);
    }

    /**
     * override test
     */
    public function BookFactory($args = array(), $alias = null)
    {
        if (isset($args['Author'])) {
            $authorArg = $args['Author'];
            unset($args['Author']);
            $author = is_string($authorArg) ? $this->$authorArg : parent::AuthorFactory($authorArg);
            $args['Author_id'] = $author->id;
        }

        return parent::BookFactory($args, $alias);
    }

    /**
     * override test
     */
    public function NotExistFactory()
    {
        return __METHOD__;
    }

    /**
     * @covers ::__call
     */
    public function testOverriddenFactory()
    {
        $this->assertEquals($this->author1->id, $this->book1->Author->id);
        $this->assertEquals('FactoryTestCaseTest::NotExistFactory', $this->notExist);
    }

    /**
     * @covers ::truncateTable
     */
    public function testTruncateTable()
    {
        $noRelation1 = $this->noRelation1;
        $this->truncateTable('HaveNoRelation');
        // Is FactoryTestCase::$repository['noRelation1'] cleared?
        $this->assertNotEquals($noRelation1->id, $this->noRelation1->id);
        $this->assertNull(HaveNoRelation::model()->findByPk($noRelation1->id));
    }

    /**
     * @covers ::__get
     */
    public function testRelations()
    {
        $this->assertInstanceOf('Book', $this->author2->Books[0]);
        foreach ($this->publisher->Series as $series) {
            if ($series->name === 'Iwanami library') {
                $this->assertCount(3, $series->Books);
                $bookNames = array_map(function(Book $book) {
                    return $book->name;
                }, $series->Books);
                $this->assertContains('The letters of Vincent Van Gogh', $bookNames);
                $this->assertContains('The Critic of Pure Reason', $bookNames);
                $this->assertContains('Discourse on Method', $bookNames);
            } elseif ($series->name === 'Iwanami science library') {
                $this->assertCount(2, $series->Books);
                $bookNames = array_map(function(Book $book) {
                    return $book->name;
                }, $series->Books);
                $this->assertContains('What is the "Elements" by Euclid?', $bookNames);
                $this->assertContains('Why does Human draw a picture?', $bookNames);
            } else {
                $this->fail('invalid series name');
            }
        }
        foreach ($this->publisher2->Series as $series) {
            if ($series->name === 'Iwanami library') {
                $this->assertCount(3, $series->Books);
                $bookNames = array_map(function(Book $book) {
                    return $book->name;
                }, $series->Books);
                $this->assertContains('The letters of Vincent Van Gogh', $bookNames);
                $this->assertContains('The Critic of Pure Reason', $bookNames);
                $this->assertContains('Discourse on Method', $bookNames);
            } elseif ($series->name === 'Iwanami science library') {
                $this->assertCount(2, $series->Books);
                $bookNames = array_map(function(Book $book) {
                    return $book->name;
                }, $series->Books);
                $this->assertContains('What is the "Elements" by Euclid?', $bookNames);
                $this->assertContains('Why does Human draw a picture?', $bookNames);
            } else {
                $this->fail('invalid series name');
            }
        }
    }
}
