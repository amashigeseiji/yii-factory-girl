<?php
class Book extends CActiveRecord
{
    public static function model($class = __CLASS__)
    {
        return parent::model($class);
    }

    public function relations()
    {
        return array(
            'Author' => array(self::BELONGS_TO, 'Author', 'Author_id'),
            'Colophon' => array(self::HAS_ONE, 'Colophon', 'Book_id')
        );
    }
}
