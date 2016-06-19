<?php

use YiiFactoryGirl\Creator;
use YiiFactoryGirl\Factory;

/**
 * @coversDefaultClass YiiFactoryGirl\Creator
 */
class CreatorTest extends YiiFactoryGirl\UnitTestCase
{
    /**
     * @dataProvider relationsSuccess
     */
    public function testRelationsSuccess()
    {
    }

    /**
     * @dataProvider createFail
     */
    public function testCreateFail()
    {
    }

    /**
     * relationsSuccess
     *
     * @return array
     */
    public function relationsSuccess()
    {
        // HAS MANY
        $hasManyBooksAuthor = Factory::getComponent()->AuthorFactory(array('name' => 'Fyodor Dostoevsky', 'relations' => array(
            'Books' => array(
                array('name' => 'Crime and Punishment'),
                array('name' => 'Notes from Underground'),
                array('id' => '45', 'name' => 'The Brothers Karamazov'),
            )
        )));
        $bookId45 = Book::model()->findByPk(45);

        // RECURSIVE
        $recursive = Factory::getComponent()->BookFactory(array('relations' => array(
            'Colophon' => array('relations' => array('PublishedBy'))
        )));

        // abbreviated
        $publisher = Factory::getComponent()->PublisherFactory(array(
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
                    return Factory::getComponent()->BookFactory()->Author;
                }
            ),
            'BELONGS TO: instanceof Author' => array(
                'assert' => 'InstanceOf',
                'result' => function() {
                    return Factory::getComponent()->BookFactory(array('relations' => array(
                        array('Author', array('name' => 'Dazai Osamu'))
                    )))->Author;
                },
                'expected' => 'Author',
            ),
            'BELONGS TO: related record name is correct' => array(
                'assert' => 'Equals',
                'result' => function() {
                    return Factory::getComponent()->BookFactory(array('relations' => array(
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
                    return Factory::getComponent()->BookFactory(array('relations' => array('Colophon')))->Colophon;
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
                    return Factory::getComponent()->AuthorFactory(array('relations' => array(
                        'Books.testAlias'
                    )), null, true)->Books[0]->name;
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

    public function createFail()
    {
        return array(
            'primary key and foreign key has same name' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'Primary key and foreign key has same name'),
                'callback'  => function() {
                    $component = Factory::getComponent();
                    $component->checkIntegrity(false);
                    $component->resetTable('SameIdToAuthor');
                    $component->resetTable('Author');
                    $component->checkIntegrity(true);
                    Factory::getComponent()->SameIdToAuthorFactory(array('id' => 1, 'relations' => array(
                        'Author' =>  array('id' => 2)
                    )), null, true);
                }
            ),
        );
    }
}
