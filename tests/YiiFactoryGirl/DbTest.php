<?php

use YiiFactoryGirl\Db;

/**
 * DbTest
 *
 * @coversDefaultClass YiiFactoryGirl\Db
 */
class DbTest extends YiiFactoryGirl\UnitTestCase
{
    /**
     * testGetDbConnectionSuccess
     *
     * @return array
     */
    public function testGetDbConnectionSuccess()
    {
        return array(
            array(
                'assert' => 'InstanceOf',
                'result' => function() {
                    return (new Db('db'))->getConnection();
                },
                'expect' => 'CDbConnection'
            )
        );
    }

    /**
     * testGetDbConnectionFail
     *
     * @return array
     */
    public function testGetDbConnectionFail()
    {
        return array(
            array(
                'exception' => array('CException', '\YiiFactoryGirl\Db.connectionID "migrate" is invalid'),
                'callback'  => function() {
                    $db = new Db('migrate');
                    $db->getConnection();
                }
            )
        );
    }
}
