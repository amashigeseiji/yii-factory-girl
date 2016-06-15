<?php
/**
 * YiiFactoryGirl_Unit_TestCase
 *
 * @see PHPUnit_Framework_TestCase
 */
class YiiFactoryGirl_Unit_TestCase extends PHPUnit_Framework_TestCase
{
    protected $subject = '';

    /**
     * invoke
     *
     * @param string $method
     * @param array $construct
     * @param array $args
     * @return mixed
     */
    protected function invoke($method, $construct = array(), $args = array())
    {
        $method = new ReflectionMethod($this->subject, $method);
        $method->setAccessible(true);

        return $method->invokeArgs($this->subject($construct), $args);
    }

    /**
     * getSubject
     *
     * @param mixed $construct
     * @return object
     */
    protected function subject($construct = array())
    {
        if (!is_array($construct)) {
            $construct = array($construct);
        }
        return (new ReflectionClass($this->subject))
            ->newInstanceArgs($construct);
    }

    /**
     * getProperty
     *
     * @param mixed $instance
     * @param mixed $get
     * @return void
     */
    protected function getProperty($instance, $get)
    {
        $reflection = new ReflectionObject($instance);
        if ($reflection->hasProperty($get)) {
            $property = $reflection->getProperty($get);
            $property->setAccessible(true);
            $result = $property->getValue($instance);
        } else {
            $result = $instance->$get;
        }
        return $result;
    }

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
        $method = new ReflectionMethod($this, 'assert'.$assertion);
        if (is_object($result) && $result instanceof Closure) {
            $result = $result();
        }
        if (is_object($expected) && $expected instanceof Closure) {
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
        } catch(Exception $e) {
            if ($e instanceof $exceptionType) {
                $exceptionMessage ?
                    $this->assertRegExp($exceptionMessage, $e->getMessage()) : $this->assertInstanceOf($exceptionType, $e);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Override to run the test and assert its state.
     *
     * @return mixed
     * @throws PHPUnit_Framework_Exception
     */
    protected function runTest()
    {
        try {
            $parent  = new ReflectionClass('PHPUnit_Framework_TestCase');

            $privates = array('name', 'data', 'dependencyInput', 'expectedException', 'expectedExceptionMessage', 'expectedExceptionCode');
            $set = array();
            foreach ($privates as $propertyName) {
                $property = $parent->getProperty($propertyName);
                $property->setAccessible(true);
                $set[$propertyName] = $property->getValue($this);
            }

            $class  = new ReflectionClass($this);
            $method = $class->getMethod($set['name']);
        }

        catch (ReflectionException $e) {
            $this->fail($e->getMessage());
        }

        if ($set['name'] === NULL) {
            throw new PHPUnit_Framework_Exception(
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

        catch (Exception $e) {
            $checkException = FALSE;

            if (is_string($set['expectedException'])) {
                $checkException = TRUE;

                if ($e instanceof PHPUnit_Framework_Exception) {
                    $checkException = FALSE;
                }

                $reflector = new ReflectionClass($set['expectedException']);

                if ($set['expectedException'] == 'PHPUnit_Framework_Exception' ||
                    $reflector->isSubclassOf('PHPUnit_Framework_Exception')) {
                    $checkException = TRUE;
                }
            }

            if ($checkException) {
                $this->assertThat(
                  $e,
                  new PHPUnit_Framework_Constraint_Exception(
                    $set['expectedException']
                  )
                );

                if (is_string($set['expectedExceptionMessage']) &&
                    !empty($set['expectedExceptionMessage'])) {
                    $this->assertThat(
                      $e,
                      new PHPUnit_Framework_Constraint_ExceptionMessage(
                        $set['expectedExceptionMessage']
                      )
                    );
                }

                if ($set['expectedExceptionCode'] !== NULL) {
                    $this->assertThat(
                      $e,
                      new PHPUnit_Framework_Constraint_ExceptionCode(
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
              new PHPUnit_Framework_Constraint_Exception(
                $set['expectedException']
              )
            );
        }

        return $testResult;
    }
}
