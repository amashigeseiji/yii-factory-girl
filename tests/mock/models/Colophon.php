<?php
class Colophon extends CActiveRecord
{
    public static function model($class = __CLASS__)
    {
        return parent::model($class);
    }

    public function relations()
    {
        return array(
            'Book' => array(self::BELONGS_TO, 'Book', 'Book_id'),
            'PublishedBy' => array(self::BELONGS_TO, 'Publisher', 'Publisher_id')
        );
    }
}
