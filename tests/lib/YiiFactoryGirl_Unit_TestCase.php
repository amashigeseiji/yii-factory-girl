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
     * assertion
     *
     * @param string $assertion
     * @param mixed $expected
     * @param mixed $result
     * @return void
     */
    protected function assertion($assertion, $expected, $result)
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
}
