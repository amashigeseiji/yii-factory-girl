<?php
class Publisher extends CActiveRecord
{
    public static function model($class = __CLASS__)
    {
        return parent::model($class);
    }

    public function relations()
    {
        return array(
            'Colophon' => array(self::HAS_MANY, 'Colophon', 'Publisher_id'),
            'Series' => array(self::HAS_MANY, 'Series', 'Publisher_id')
        );
    }
}
