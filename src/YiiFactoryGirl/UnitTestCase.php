<?php

namespace YiiFactoryGirl;

/**
 * YiiFactoryGirl\UnitTestCase
 *
 * @see \CTestCase
 * @see \PHPUnit_Framework_TestCase
 */
abstract class UnitTestCase extends \CTestCase
{
    /**
     * assertSuccess
     *
     * @param string $assertion
     * @param mixed $expected
     * @param mixed $result
     * @return void
     */
    protected function assertSuccess($assertion, $result, $expected = null)
    {
        $method = new \ReflectionMethod($this, 'assert'.$assertion);
        if (is_object($result) && $result instanceof \Closure) {
            $result = $result();
        }
        if (is_object($expected) && $expected instanceof \Closure) {
            $expected = $expected($result);
        }
        return $method->getNumberOfParameters() > 2 ?
            $method->invoke($this, $expected, $result) : $method->invoke($this, $result);
    }

    /**
     * assertFail
     *
     * @param array $exception
     * @param callable $callback
     * @return void
     */
    protected function assertFail($exception, $callback)
    {
        @list($exceptionType, $exceptionMessage) = $exception;
        try {
            $callback();
            $this->fail('Unexpected build success');
        } catch(\Exception $e) {
            if ($e instanceof $exceptionType) {
                $this->assertThat(
                    $e,
                    new \PHPUnit_Framework_Constraint_Exception($exceptionType)
                );
                if ($exceptionMessage) {
                    $this->assertThat(
                        $e,
                        new \PHPUnit_Framework_Constraint_ExceptionMessage($exceptionMessage)
                    );
                }
            } else {
                throw $e;
            }
        }
    }

    /**
     * Override to run the test and assert its state.
     *
     * If test name **Success or **Fail, call
     * assertSuccess/assertFail automatically.
     *
     * @return mixed
     * @throws PHPUnit_Framework_Exception
     * @see \PHPUnit_Framework_TestCase::runTest
     */
    protected function runTest()
    {
        try {
            $parent  = new \ReflectionClass('PHPUnit_Framework_TestCase');

            $privates = array('name', 'data', 'dependencyInput', 'expectedException', 'expectedExceptionMessage', 'expectedExceptionCode');
            $set = array();
            foreach ($privates as $propertyName) {
                $property = $parent->getProperty($propertyName);
                $property->setAccessible(true);
                $set[$propertyName] = $property->getValue($this);
            }

            $class  = new \ReflectionClass($this);
            $method = $class->getMethod($set['name']);
        }

        catch (\ReflectionException $e) {
            $this->fail($e->getMessage());
        }

        if ($set['name'] === NULL) {
            throw new \PHPUnit_Framework_Exception(
              'PHPUnit_Framework_TestCase::$name must not be NULL.'
            );
        }

        try {
            $args = array_merge($set['data'], $set['dependencyInput']);
            if (preg_match('/Success$/', $set['name'])) {
                $method->invokeArgs($this, $args);
                $testResult = call_user_func_array(array($this, 'assertSuccess'), $args);
            } elseif (preg_match('/Fail$/', $set['name'])) {
                $testResult = call_user_func_array(array($this, 'assertFail'), $args);
            } else {
                $testResult = $method->invokeArgs($this, $args);
            }
        }

        catch (\Exception $e) {
            $checkException = FALSE;

            if (is_string($set['expectedException'])) {
                $checkException = TRUE;

                if ($e instanceof \PHPUnit_Framework_Exception) {
                    $checkException = FALSE;
                }

                $reflector = new \ReflectionClass($set['expectedException']);

                if ($set['expectedException'] == 'PHPUnit_Framework_Exception' ||
                    $reflector->isSubclassOf('\PHPUnit_Framework_Exception')) {
                    $checkException = TRUE;
                }
            }

            if ($checkException) {
                $this->assertThat(
                  $e,
                  new \PHPUnit_Framework_Constraint_Exception(
                    $set['expectedException']
                  )
                );

                if (is_string($set['expectedExceptionMessage']) &&
                    !empty($set['expectedExceptionMessage'])) {
                    $this->assertThat(
                      $e,
                      new \PHPUnit_Framework_Constraint_ExceptionMessage(
                        $set['expectedExceptionMessage']
                      )
                    );
                }

                if ($set['expectedExceptionCode'] !== NULL) {
                    $this->assertThat(
                      $e,
                      new \PHPUnit_Framework_Constraint_ExceptionCode(
                        $set['expectedExceptionCode']
                      )
                    );
                }

                return;
            } else {
                throw $e;
            }
        }

        if ($set['expectedException'] !== NULL) {
            $this->assertThat(
              NULL,
              new \PHPUnit_Framework_Constraint_Exception(
                $set['expectedException']
              )
            );
        }

        return $testResult;
    }
}
