<?php
class Series extends CActiveRecord
{
    public static function model($class = __CLASS__)
    {
        return parent::model($class);
    }

    public function relations()
    {
        return array(
            'PublishedBy' => array(self::BELONGS_TO, 'Publisher', 'Publisher_id'),
            'Books' => array(self::HAS_MANY, 'Book', 'Series_id')
        );
    }
}
