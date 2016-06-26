<?php

use YiiFactoryGirl\FactoryTestCase;

/**
 * @coversDefaultClass YiiFactoryGirl\FactoryTestCase
 */
class FactoryTestCaseTest extends FactoryTestCase
{
    public static function setUpBeforeClass()
    {
        YiiFactoryGirl\Factory::getComponent()->checkIntegrity(false);
        YiiFactoryGirl\Factory::getComponent()->truncateTables();
        YiiFactoryGirl\Factory::getComponent()->checkIntegrity(true);
    }

    protected $factories = array(
        'noRelation1'    => 'HaveNoRelation',
        'noRelation2'    => array('HaveNoRelation', array('name' => 'hoge')),
        'invalidDefinition' => 2,
        'author1'    => 'Author',
        'book1'    => array('Book', array('Author' => 'author1')),
        'notExist' => array('NotExist'),
        'author2'    => array('Author', array(
            'relations' => array('Books')
        )),
        'publisher1' => array('Publisher', array('name' => 'Iwanami shoten publishers'), 'iwanami'),
        'publisher2' => array('Publisher', array('name' => 'Iwanami shoten publishers'), 'iwanami')
    );

    /**
     * testFactoryTestCaseSuccess
     *
     * @return array
     */
    public function testFactoryTestCaseSuccess()
    {
        return array(
            'noRelation1 is exists' => array(
                'assert' => 'NotNull',
                'result' => function() {
                    return HaveNoRelation::model()->findByPk($this->noRelation1->id);
                },
            ),
            'noRelation2 is exists' => array(
                'assert' => 'NotNull',
                'result' => function() {
                    return HaveNoRelation::model()->findByPk($this->noRelation2->id);
                },
            ),
            'noRelation2\'s name is hoge' => array(
                'assert' => 'Equals',
                'result' => $this->noRelation2->name,
                'expect' => 'hoge'
            ),
            'ActiveRecord cache in FactoryTestCase::$repository' => array(
                'assert' => 'Same',
                'result' => $this->noRelation1->id,
                'expect' => $this->noRelation1->id
            ),
        );
    }

    /**
     * testGetFail
     *
     * test for FactoryTestCase::__get
     *
     * @return array
     */
    public function testGetFail()
    {
        return array(
            'definition is invalid' => array(
                'expection' => array('YiiFactoryGirl\FactoryException', '$factories[invalidDefinition] is invalid definition.'),
                'callback' => function() {
                    $this->invalidDefinition;
                }
            ),
            'unknown property' => array(
                'expection' => array('YiiFactoryGirl\FactoryException', 'Unknown property'),
                'callback' => function() {
                    $this->unknown;
                }
            )
        );
    }

    /**
     * testCallFail
     *
     * test for FactoryTestCase::__call
     * @return array
     */
    public function testCallFail()
    {
        return array(
            'unknown method is called' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'Call to undefined method'),
                'callback' => function() {
                    $this->unknownMethod();
                }
            )
        );
    }

    /**
     * testOverriddenFactorySuccess
     *
     * @return array
     */
    public function testOverriddenFactorySuccess()
    {
        return array(
            'BookFactory method corrects relation' => array(
                'assert' => 'Equals',
                'result' => function() {
                    return $this->author1->id;
                },
                'expect' => $this->book1->Author->id
            ),
            'call success when class name not exist but Factory method defined' => array(
                'assert' => 'Equals',
                'result' => function() {
                    return $this->notExist;
                },
                'expect' => 'FactoryTestCaseTest::NotExistFactory'
            ),
        );
    }

    /**
     * testTruncateTableSuccess
     *
     * @return array
     */
    public function testTruncateTableSuccess()
    {
        $noRelation1 = $this->noRelation1;
        return array(
            'once record deleted, another record create on call' => array(
                'assert' => 'NotEquals',
                'result' => function() {
                    $this->truncateTable('HaveNoRelation');
                    // Is FactoryTestCase::$repository['noRelation1'] cleared?
                    return $this->noRelation1->id;
                },
                'expect' => $noRelation1->id
            ),
            'instance on memory is not exist in database' => array(
                'assert' => 'Null',
                'result' => function() use ($noRelation1) {
                    return HaveNoRelation::model()->findByPk($noRelation1->id);
                }
            )
        );
    }

    /**
     * testRelations
     *
     * @return array
     */
    public function testRelationsSuccess()
    {
        $books = function(Publisher $publisher, $seriesName) {
            foreach ($publisher->Series as $series) {
                if ($series->name === $seriesName) {
                    return array_map(function(Book $book) {
                        return $book->name;
                    }, $series->Books);
                }
            }
        };

        return array(
            'author2 has Book relation' => array(
                'assert' => 'InstanceOf',
                'result' => $this->author2->Books[0],
                'expect' => 'Book'
            ),

            // recursive relation

            // publisher1
            // Iwanami library
            'publisher1\'s Iwanami library series has 3 books' => array(
                'assert' => 'Count',
                'result' => function() use ($books) {
                    return $books($this->publisher1, 'Iwanami library');
                },
                'expect' => 3
            ),
            'publisher1\'s Iwanami library contains "The letters of Vincent Van Gogh"' => array(
                'assert' => 'Contains',
                'result' => function() use ($books) {
                    return $books($this->publisher1, 'Iwanami library');
                },
                'expect' => 'The letters of Vincent Van Gogh',
            ),
            'publisher1\'s Iwanami library contains "The Critic of Pure Reason"' => array(
                'assert' => 'Contains',
                'result' => function() use ($books) {
                    return $books($this->publisher1, 'Iwanami library');
                },
                'expect' => 'The Critic of Pure Reason',
            ),
            'publisher1\'s Iwanami library contains "Discourse on Method"' => array(
                'assert' => 'Contains',
                'result' => function() use ($books) {
                    return $books($this->publisher1, 'Iwanami library');
                },
                'expect' => 'Discourse on Method',
            ),

            // Iwanami science library
            'publisher1\'s Iwanami science library series has 2 books' => array(
                'assert' => 'Count',
                'result' => function() use ($books) {
                    return $books($this->publisher1, 'Iwanami science library');
                },
                'expect' => 2
            ),
            'publisher1\'s Iwanami science library contains "What is the "Elements" by Euclid?"' => array(
                'assert' => 'Contains',
                'result' => function() use ($books) {
                    return $books($this->publisher1, 'Iwanami science library');
                },
                'expect' => 'What is the "Elements" by Euclid?',
            ),
            'publisher1\'s Iwanami science library contains "Why does Human draw a picture?"' => array(
                'assert' => 'Contains',
                'result' => function() use ($books) {
                    return $books($this->publisher1, 'Iwanami science library');
                },
                'expect' => 'Why does Human draw a picture?',
            ),

            // publisher2 has another record
            // Iwanami library
            'publisher2\'s Iwanami library series has 3 books' => array(
                'assert' => 'Count',
                'result' => function() use ($books) {
                    return $books($this->publisher2, 'Iwanami library');
                },
                'expect' => 3
            ),
            'publisher2\'s Iwanami library contains "The letters of Vincent Van Gogh"' => array(
                'assert' => 'Contains',
                'result' => function() use ($books) {
                    return $books($this->publisher2, 'Iwanami library');
                },
                'expect' => 'The letters of Vincent Van Gogh',
            ),
            'publisher2\'s Iwanami library contains "The Critic of Pure Reason"' => array(
                'assert' => 'Contains',
                'result' => function() use ($books) {
                    return $books($this->publisher2, 'Iwanami library');
                },
                'expect' => 'The Critic of Pure Reason',
            ),
            'publisher2\'s Iwanami library contains "Discourse on Method"' => array(
                'assert' => 'Contains',
                'result' => function() use ($books) {
                    return $books($this->publisher2, 'Iwanami library');
                },
                'expect' => 'Discourse on Method',
            ),

            // Iwanami science library
            'publisher2\'s Iwanami science library series has 2 books' => array(
                'assert' => 'Count',
                'result' => function() use ($books) {
                    return $books($this->publisher2, 'Iwanami science library');
                },
                'expect' => 2
            ),
            'publisher2\'s Iwanami science library contains "What is the "Elements" by Euclid?"' => array(
                'assert' => 'Contains',
                'result' => function() use ($books) {
                    return $books($this->publisher2, 'Iwanami science library');
                },
                'expect' => 'What is the "Elements" by Euclid?',
            ),
            'publisher2\'s Iwanami science library contains "Why does Human draw a picture?"' => array(
                'assert' => 'Contains',
                'result' => function() use ($books) {
                    return $books($this->publisher2, 'Iwanami science library');
                },
                'expect' => 'Why does Human draw a picture?',
            ),
        );
    }


    /* override test */

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

    public function NotExistFactory()
    {
        return __METHOD__;
    }
}
