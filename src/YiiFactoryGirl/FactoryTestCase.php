<?php

/**
 * FactoryTestCase
 *
 * DESCRIPTION:
 *  If protected property "$factories" is defined,
 *  you can get AR instance with "$this->{:$name}"
 *
 * SAMPLE:
 * ```
 * <?php
 *  use YiiFactoryGirl\FactoryTestCase;
 *  class HogeTest case extends FactoryTestCase
 *  {
 *      protected $factory = array(
 *          'User1' => array('User'),
 *          'User2' => array('User', array('name' => 'testname'), 'registered')
 *      )
 *
 *      public function testIsUser()
 *      {
 *          // you can get AR instance by $factory key,
 *          // and this is recorded by FactoryGirl.
 *          $this->assertEquals($this->User1->id, User::findByPk($this->User1->id));
 *      }
 *
 *      // you can override factory method with following format.
 *      // function {:ModelName}Factory() {}
 *      public function UserFactory($args = array(), $alias = null)
 *      {
 *          // Do handle arguments or create some required data
 *          return parent::UserFactory();
 *      }
 *  }
 * ```
 *
 * @author Seiji Amashige <tenjuu99@gmail.com>
 * @package YiiFactoryGirl
 */

namespace YiiFactoryGirl;

abstract class FactoryTestCase extends UnitTestCase
{
    /**
     * @var $factories
     * Definition of factory data.
     *
     * Define $factories in sub-classes like this:
     * ```
     *   $factories = array(
     *       'user1' => array('User', array('name' => 'test'), 'default'),
     *       'user2' => 'User',
     *       'user3' => array('User', array('relations' => array('Identity'))) // definition of relation
     *   )
     * ```
     *
     * You can get AR instance in testcase:
     * ```
     *   $this->user1;
     *   $this->user2;
     *   $this->user3->Identity;
     * ```
     */
    protected $factories = array();

    /**
     * @var $repository
     * Cache instance of factorized record.
     */
    private $repository = array();

    /**
     * __get
     *
     * If given $name is defined in $factories as key,
     * create AR record and cache in $repository.
     *
     * @param string $name property name
     * @return CActiveRecord
     * @throws YiiFactoryGirl\FactoryException
     */
    public function __get($name)
    {
        if (!empty($this->factories) && isset($this->factories[$name])) {
            if (!isset($this->repository[$name])) {
                $args = $this->factories[$name];
                if (is_string($args)) {
                    $class = $args;
                    $args = array();
                } elseif (is_array($args)) {
                    $class = array_shift($args);
                } else {
                    throw new FactoryException('$factories[' . $name . '] is invalid definition.');
                }
                $method = $class . Factory::FACTORY_METHOD_SUFFIX;
                $this->repository[$name] = call_user_func_array(array($this, $method), $args);
            }

            return $this->repository[$name];
        }

        throw new FactoryException("Unknown property '{$name}' for class '". get_class($this) ."'.");
    }

    /**
     * __call
     *
     * @param string $name method name
     * @param array $args
     * @return mixed
     * @throws YiiFactoryGirl\FactoryException
     */
    public function __call($name, $args)
    {
        if (Factory::isFactoryMethod($name)) {
            return call_user_func_array(array(Factory::getComponent(), $name), $args);
        }

        throw new FactoryException('Call to undefined method ' . get_class($this) . "::{$name}().");
    }

    /**
     * truncateTable
     *
     * If record is deleted, $repository's ActiveRecord will be inconsistent.
     *
     * @param string $tableName table name
     * @return void
     */
    protected function truncateTable($tableName)
    {
        Factory::getComponent()->truncateTable($tableName);
        foreach ($this->repository as $key => $record) {
            if ($record->tableName() === $tableName) {
                unset($this->repository[$key]);
            }
        }
    }
}
