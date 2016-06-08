<?php
class Composite extends CActiveRecord
{
    public function tableName()
    {
        return 'CompositePrimaryKeyTable';
    }

    public static function model($class = __CLASS__)
    {
        return parent::model($class);
    }
}
