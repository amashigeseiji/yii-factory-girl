<?php
class Author extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'Books' => array(self::HAS_MANY, 'Book', 'Author_id'),
            'SameId' => array(self::HAS_ONE, 'SameIdToAuthor', 'id')
        );
    }
}
