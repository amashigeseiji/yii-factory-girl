<?php

use YiiFactoryGirl\FactoryData;

/**
 * @coversDefaultClass YiiFactoryGirl\FactoryData
 */
class FactoryDataTest extends YiiFactoryGirl\UnitTestCase
{
    /**
     * testFromFile
     *
     * @dataProvider fromFileSuccess
     */
    public function testFromFileSuccess()
    {
    }

    /**
     * @dataProvider getAttributeFromFileSuccess
     */
    public function testGetAttributesFromFileSuccess()
    {
    }

    /**
     * @dataProvider constructFail
     */
    public function testConstructFail()
    {
    }

    /**
     * @dataProvider instantiateSuccess
     */
    public function testInstantiateSuccess($assert, callable $callback, $expected = null)
    {
    }

    /**
     * @dataProvider normalizeAttributesSuccess
     */
    public function testNormalizeAttributesSuccess()
    {
    }

    /**
     * fromFileSuccess
     *
     * @return array
     */
    public function fromFileSuccess()
    {
        $test = function($path) {
            return array(
                'assert' => 'InstanceOf',
                'result' => function() use($path) {
                    return FactoryData::fromFile($path, YiiFactoryGirl\Factory::FACTORY_FILE_SUFFIX.'.php');
                },
                'YiiFactoryGirl\FactoryData'
            );
        };
        $tests = array();
        $instance = YiiFactoryGirl\Factory::getComponent();
        $paths = CFileHelper::findFiles($instance->getBasePath());
        foreach ($paths as $path) {
            $tests[] = $test($path);
        }

        return $tests;
    }

    /**
     * getAttributeFromFileSuccess
     *
     * @return array
     */
    public function getAttributeFromFileSuccess()
    {
        extract($this->path('Book'));
        return array(
            'without arguments or alias' => array(
                'assert' => 'Equals',
                'result' => FactoryData::fromFile($path, $suffix)->getAttributes(),
                'expected' => array('name' => 'default value')
            ),
            'with alias' => array(
                'assert' => 'Equals',
                'result' => FactoryData::fromFile($path, $suffix)->getAttributes(array(), 'testAlias'),
                'expected' => array('name' => 'inserted by alias')
            ),
            'with arguments and alias' => array(
                'assert' => 'Equals',
                'result' => FactoryData::fromFile($path, $suffix)->getAttributes(array('id' => 1), 'testAlias'),
                'expected' => array('id' => 1, 'name' => 'inserted by alias')
            )
        );
    }

    /**
     * constructFail
     *
     * @return void
     */
    public function constructFail()
    {
        return array(
            'file does not exist' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'NotExistFactory.php" does not seem to be factory data file.'),
                'callback' => function() {
                    extract($this->path('NotExist'));
                    FactoryData::fromFile($path, $suffix);
                }
            ),
            'invalid format file' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'expected to return config array with "attributes" inside'),
                'callback' => function() {
                    extract($this->path('invalid', 'application.tests.invalidfile'));
                    FactoryData::fromFile($path, $suffix);
                }
            ),
            'invalid alias' => array(
                'exception' => array('YiiFactoryGirl\FactoryException', 'Alias "invalidAlias" not found'),
                'callback' => function() {
                    (new FactoryData('Book'))->getAttributes(array(), 'invalidAlias');
                }
            ),
        );
    }

    /**
     * instantiateSuccess
     *
     * @return array
     */
    public function instantiateSuccess()
    {
        $method = new ReflectionMethod('YiiFactoryGirl\FactoryData::instantiate');
        $method->setAccessible(true);

        return array(
            'ActiveRecord' => array(
                'assert' => 'InstanceOf',
                'callback' => function() use($method) {
                    return $method->invoke(new FactoryData('Book'));
                },
                'expected' => 'CActiveRecord',
            ),
            'FormModel' => array(
                'assert' => 'InstanceOf',
                'callback' => function() use($method) {
                    return $method->invoke(new FactoryData('BookForm'));
                },
                'expected' => 'CFormModel',
            )
        );
    }

    /**
     * dataProvider for testNormalizeArguments
     *
     * @return array
     */
    public function normalizeAttributesSuccess()
    {
        $method = new ReflectionMethod('YiiFactoryGirl\FactoryData::normalizeAttributes');
        $factory = new FactoryData('Book');
        $method->setAccessible(true);

        return array(
            'with attributes' => array(
                'assert'   => 'Same',
                'result'   => $method->invoke($factory, array('id' => 1)),
                'expected' => array(
                    'attributes' => array('id' => 1),
                    'relations' => array()
                ),
            ),

            'with relation' => array(
                'assert' => 'Same',
                'result' => $method->invoke($factory, array('name' => 'hoge', 'relations' => array(
                    array('Identity', array('test' => 'hoge'), 'alias'),
                    array('Hoge'),
                ))),
                'expected' => array(
                    'attributes' => array('name' => 'hoge'),
                    'relations'  => array(
                        array('Identity', array('test' => 'hoge'), 'alias'),
                        array('Hoge', array(), null),
                    )
                ),
            ),

            'with relation2' => array(
                'assert' => 'Same',
                'result' => $method->invoke($factory, array('name' => 'hoge', 'fuga' => 'tetete', 'relations' => array(
                        'Identity' => array('test' => 'hoge'),
                        'Hoge' => 'HogeAlias',
                        'Fuga',
                    ))
                ),
                'expected' => array(
                    'attributes' => array('name' => 'hoge', 'fuga' => 'tetete'),
                    'relations'  => array(
                        array('Identity', array('test' => 'hoge'), null),
                        array('Hoge', array(), 'HogeAlias'),
                        array('Fuga', array(), null)
                    )
                ),
            ),

            'HAS_MANY' => array(
                'assert' => 'Same',
                'result' => $method->invoke($factory, array('relations' => array(
                    // This format is to be interpreted as HAS_MANY relation.
                    'Hoge' => array(
                        array('id' => 1),
                        array('id' => 2),
                    ))
                )),
                'expected' => array(
                    'attributes' => array(),
                    'relations' => array(
                        array('Hoge', array('id' => 1), null),
                        array('Hoge', array('id' => 2), null),
                    )
                ),
            ),

            'Model name alias' => array(
                'assert' => 'Same',
                'result' => $method->invoke($factory, array('relations' => array(
                    'Hoge.relationAlias',
                    'Fuga.alias' => array('id' => 2)
                ))),
                'expected' => array(
                    'attributes' => array(),
                    'relations'  => array(
                        array('Hoge', array(), 'relationAlias'),
                        array('Fuga', array('id' => 2), 'alias')
                    )
                ),
            ),

            'abbreviate' => array(
                'assert' => 'Same',
                'result' => $method->invoke($factory, array('Author' => array())),
                'expected' => array(
                    'attributes' => array(),
                    'relations' => array(
                        array('Author', array(), null)
                    )
                )
            )
        );
    }

    private function path($class, $pathAlias = 'application.tests.factories')
    {
        $instance = YiiFactoryGirl\Factory::getComponent();
        $suffix = $instance->factoryFileSuffix . '.php';
        return array('path' => \Yii::getPathOfAlias($pathAlias) . DIRECTORY_SEPARATOR . $class . $suffix, 'suffix' => $suffix);
    }
}
