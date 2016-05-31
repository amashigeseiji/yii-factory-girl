<?php
class SameIdToAuthor extends CActiveRecord
{
    public static function model($class = __CLASS__)
    {
        return parent::model($class);
    }

    public function relations()
    {
        return array(
            'Author' => array(self::BELONGS_TO, 'Author', 'id')
        );
    }
}
