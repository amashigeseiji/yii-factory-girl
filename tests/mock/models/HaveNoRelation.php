<?php
class HaveNoRelation extends CActiveRecord
{
    public static $staticProperty = null;

    public $publicProperty = null;

    private $privateProperty = null;

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
}
