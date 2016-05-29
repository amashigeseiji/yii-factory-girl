<?php

/**
 * FactoryGirl
 *
 * Wrapper class of YiiFactoryGirl\Factory.
 *
 * @see YiiFactoryGirl\Factory
 * @author Seiji Amashige <tenjuu99@gmail.com>
 * @package YiiFactoryGirl
 */

namespace YiiFactoryGirl;

class FactoryGirl
{
    /**
     * FACTORY_METHOD_SUFFIX
     */
    const FACTORY_METHOD_SUFFIX = 'Factory';

    /**
     * @var $factories
     */
    private static $factories = null;

    /**
     * @var $reflectionMethods
     */
    private static $reflectionMethods = array();

    /**
     * @var $callable
     * callable methods cache
     */
    private static $callable = array();

    /**
     * __callStatic
     *
     * This method emulates factory method
     * if called-method format is '{:ModelName}Factory'.
     *
     * @param string $name method name
     * @param array $args
     * @return mixed
     * @throws YiiFactoryGirl\FactoryException
     */
    public static function __callStatic($name, $args)
    {
        if (!self::isCallable($name)) {
            throw new FactoryException('Call to undefined method ' . __CLASS__ . "::{$name}().");
        }

        $relations = null;
        if (in_array($name, self::$factories)) {
            extract(self::normalizeArguments($name, $args));
            $name = 'create';
        }

        $result = call_user_func_array(array(self::getInstance(), $name), $args);

        $isActiveRecord = is_object($result) ? is_subclass_of($result, 'CActiveRecord') : false;
        if ($isActiveRecord && $relations) {
            self::createRelations($result, $relations);
        }

        return $result;
    }

    /**
     * isCallable
     *
     * @param string $name method name
     * @return bool
     */
    public static function isCallable($name)
    {
        if (empty(self::$callable)) {
            self::setFactories();
            self::setReflectionMethods();
            self::$callable = array_merge(self::$factories, self::$reflectionMethods);
        }

        if (in_array($name, self::$callable)) {
            return true;
        }

        if (preg_match('/(.*)'.self::FACTORY_METHOD_SUFFIX.'$/', $name, $match)) {
            try {
                $reflection = new \ReflectionClass($match[1]);
                if ($reflection->isSubclassOf('CActiveRecord')) {
                    self::$factories[] = $name;
                    self::$callable[] = $name;
                    return true;
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * setFactories
     *
     * @return void
     */
    private static function setFactories()
    {
        $paths = \CFileHelper::findFiles(
            self::getInstance()->basePath,
            array('absolutePaths' => false)
        );
        self::$factories = array_map(function($path) {
            return explode('.', $path)[0];
        }, $paths);
    }

    /**
     * setReflectionMethods
     *
     * @return void
     */
    private static function setReflectionMethods()
    {
        $reflection = new \ReflectionClass('YiiFactoryGirl\Factory');
        foreach ($reflection->getMethods() as $method) {
            if ($method->class !== 'CComponent' && $method->class !== 'CApplicationComponent' && $method->isPublic()) {
                self::$reflectionMethods[] = $method->name;
            }
        }
    }

    /**
     * getInstance
     *
     * If not set factorygirl component, this method set it.
     * component name is expected as `factorygirl`.
     * @return YiiFactoryGirl\Factory
     */
    public static function getInstance()
    {
        if (!\Yii::app()->hasComponent('factorygirl')) {
            \Yii::app()->setComponent('factorygirl', array('class' => 'YiiFactoryGirl\Factory'));
        }

        return \Yii::app()->factorygirl;
    }

    /**
     * createRelations
     *
     * @param CActiveRecord $activeRecord
     * @param Array $relations
     * @return void
     */
    private static function createRelations(\CActiveRecord &$activeRecord, Array $relations)
    {
        foreach ($relations as $relation) {
            @list($model, $args, $alias) = $relation;
            self::createRelation($activeRecord, $model, $args, $alias);
        }
        $activeRecord->refresh();
    }

    /**
     * createRelation
     *
     * @param CActiveRecord $activeRecord
     * @param string $name
     * @param array $args
     * @param string|null $alias
     * @return void
     * @throws YiiFactoryGirl\FactoryException
     */
    private static function createRelation(\CActiveRecord &$activeRecord, $name, $args = array(), $alias = null)
    {
        if (is_null($args)) {
            $args = array();
        }

        if ($relation = $activeRecord->getActiveRelation($name)) {
            $factoryMethod = $relation->className . self::FACTORY_METHOD_SUFFIX;
            switch ($relation){
            case $relation instanceof \CBelongsToRelation:
                $related = self::$factoryMethod($args, $alias);
                // FIXME If primary key name equals foreign key name, it will causes duplicate entry.
                if ($relation->foreignKey === $activeRecord->tableSchema->primaryKey && $activeRecord->getPrimaryKey() != $related->getPrimaryKey()) {
                    throw new FactoryException('Primary key and foreign key has same name, and both values are not same. Please set primary key manually not to cause duplicate entry.');
                }
                $activeRecord->{$relation->foreignKey} = $related->getPrimaryKey();
                $activeRecord->update();
                $activeRecord->$name = $related;
                break;
            case $relation instanceof \CHasOneRelation:
            case $relation instanceof \CHasManyRelation:
                $args[$relation->foreignKey] = $activeRecord->primaryKey;
                $result = self::$factoryMethod($args, $alias);
                if ($relation instanceof \CHasOneRelation) {
                    $index = false;
                } else {
                    // todo test
                    $index = is_null($relation->index) ? true : $result[$relation->index];
                }
                $activeRecord->addRelatedRecord($name, $result, $index);
                break;
            case $relation instanceof \CManyManyRelation:
                throw new FactoryException('Relation type ManyMany is unsupported.');
                break;
            default:
                throw new FactoryException('Relation type ' . get_class($relation) . ' is unsupported.');
                break;
            }
        }
    }

    /**
     * normalizeArguments
     *
     * @param String $name
     * @param Array $args
     * @return Array
     */
    private static function normalizeArguments($name, Array $args)
    {
        $model = str_replace(self::FACTORY_METHOD_SUFFIX, '', $name);
        $relations = array();
        if (isset($args[0]['relations'])) {
            $relations = self::parseRelationArguments($args[0]['relations']);
            unset($args[0]['relations']);
        }

        return array(
            'args' => array_merge(array($model), $args),
            'relations' => $relations
        );
    }

    /**
     * parseRelationArguments
     *
     * @param Array $relationsArguments
     * @return Array
     * @throws YiiFactoryGirl\FactoryException
     */
    private static function parseRelationArguments(Array $relationsArguments)
    {
        $relations = array();

        /**
         * following format will be assumed as HAS_MANY relation:
         *
         * FactoryGirl::HogeFactory(array('relations' => array(
         *   ':RelatedObject' => array(
         *       array('Category_id' => 1, 'sortOrder' => 1),
         *       array('Category_id' => 2, 'sortOrder' => 2)
         *   ),
         * )));
         *
         * @param Array $args
         * @return Array
         */
        $hasManyRelation = function(Array $args) {
            $hasMany = array();
            foreach ($args as $key => $val) {
                if (is_int($key) && is_array($val)) {
                    $hasMany[] = $val;
                }
            }
            return $hasMany;
        };

        foreach ($relationsArguments as $key => $value) {
            $model = '';
            $args = array();
            $alias = null;

            if (is_int($key)) { // normal array
                if (is_string($value)) {
                    $model = $value;
                } elseif (is_array($value)) {
                    @list($model, $args, $alias) = $value;
                    if (is_null($args)) {
                        $args = array();
                    }
                } else {
                    throw new FactoryException('Invalid arguments.');
                }
            } else { // hash
                $model = $key;
                is_string($value) ? $alias = $value : $args = $value;
            }

            if (strpos($model, '.')) {
                list($model, $alias) = explode('.', $model);
            }

            if ($hasMany = $hasManyRelation($args)) {
                foreach ($hasMany as $hasManyArgs) {
                    $relations[] = array($model, $hasManyArgs, $alias);
                }
            } else {
                $relations[] = array($model, $args, $alias);
            }
        }

        return $relations;
    }
}
