<?php
/**
 * ActiveRecord creator
 *
 * @author Seiji Amashige <tenjuu99@gmail.com>
 * @package YiiFactoryGirl
 */

namespace YiiFactoryGirl;

/**
 * Creator
 */
class Creator
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
     * create
     *
     * @param \CActiveRecord $obj
     * @return \CActiveRecord
     */
    public static function create(\CActiveRecord $obj)
    {
        $schema = Factory::getDbConnection()->getSchema();
        $builder = $schema->getCommandBuilder();
        $table = $schema->getTable($obj->tableName());

        // attributes to insert
        $attributes = $obj->getAttributes();

        // make sure it gets inserted
        $schema->checkIntegrity(false);
        $builder->createInsertCommand($table, $attributes)->execute();

        $primaryKey = $table->primaryKey;
        if ($table->sequenceName !== null) {
            if (is_string($primaryKey) && !isset($attributes[$primaryKey])) {
                $obj->{$primaryKey} = $builder->getLastInsertID($table);
            } elseif(is_array($primaryKey)) {
                foreach($primaryKey as $pk) {
                    if (!isset($attributes[$pk])) {
                        $obj->{$pk} = $builder->getLastInsertID($table);
                        break;
                    }
                }
            }
        }

        $schema->checkIntegrity(true);

        $obj->setScenario('update');
        $obj->setIsNewRecord(false);

        return $obj;
    }

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

        if (in_array($name, self::$factories)) {
            $relations = null;
            extract(self::normalizeArguments(str_replace(self::FACTORY_METHOD_SUFFIX, '', $name), $args));
            @list($class, $attr, $alias) = $args;
            $result = Factory::getComponent()->create($class, $attr, $alias);
            if ($relations) {
                self::createRelations($result, $relations);
            }
        } else {
            $result = call_user_func_array(self::$name, $args);
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
                //do nothing
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
        self::$factories = array_map(function($path) {
            return explode('.', $path)[0];
        }, Factory::getComponent()->getFiles(false));
    }

    /**
     * setReflectionMethods
     *
     * @return void
     */
    private static function setReflectionMethods()
    {
        $reflection = new \ReflectionClass('YiiFactoryGirl\Creator');
        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic()) {
                self::$reflectionMethods[] = $method->name;
            }
        }
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
    private static function normalizeArguments($model, Array $arguments)
    {
        $args = isset($arguments[0]) ? $arguments[0] : array();
        $alias = isset($arguments[1]) ? $arguments[1] : null;
        $relations = array();

        if ($args) {
            if (@class_exists($model)) {
                $rels = $model::model()->getMetaData()->relations;
                foreach ($args as $key => $val) {
                    if (isset($rels[$key])) {
                        $relations[$key] = $val;
                        unset($args[$key]);
                    }
                }
            }
            if (isset($args['relations'])) {
                $relations = array_merge($args['relations'], $relations);
                unset($args['relations']);
            }
        }

        return array(
            'args' => array($model, $args, $alias),
            'relations' => $relations ? self::parseRelationArguments($relations) : array()
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
