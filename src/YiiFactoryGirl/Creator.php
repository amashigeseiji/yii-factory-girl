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
     * create
     *
     * @param \CActiveRecord $obj
     * @return \CActiveRecord
     */
    public static function create(\CActiveRecord $obj, $relations = array())
    {
        $schema = Factory::getComponent()->getDbConnection()->getSchema();
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

        if ($relations) {
            self::createRelations($obj, $relations);
        }

        return $obj;
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
    private static function createRelation(\CActiveRecord &$activeRecord, $name, array $args = array(), $alias = null)
    {
        if (!$relation = $activeRecord->getActiveRelation($name)) {
            return;
        }

        switch ($relation) {
            case $relation instanceof \CBelongsToRelation:
                $related = Factory::getComponent()->create($relation->className, $args, $alias);
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
                $result = Factory::getComponent()->create($relation->className, $args, $alias);
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
