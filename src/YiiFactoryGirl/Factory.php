<?php

namespace YiiFactoryGirl;

class Factory extends \CApplicationComponent
{
    const LOG_CATEGORY = 'yii-factory-girl';

    /**
     * @var string the name of the initialization script that would be executed before the whole test set runs.
     * Defaults to 'init.php'. If the script does not exist, every table with a factory file will be reset.
     */
    public $initScript = 'init.php';
    /**
     * @var string the suffix for factory initialization scripts.
     * If a table is associated with such a script whose name is TableName suffixed this property value,
     * then the script will be executed each time before the table is reset.
     */
    public $initScriptSuffix = '.init.php';
    /**
     * @var string the base path containing all factories. Defaults to null, meaning
     * the path 'protected/tests/factories'.
     */
    public $basePath;
    /**
     * @var string the ID of the database connection. Defaults to 'db'.
     * Note, data in this database may be deleted or modified during testing.
     * Make sure you have a backup database.
     */
    public $connectionID = 'db';
    /**
     * @var array list of database schemas that the test tables may reside in. Defaults to
     * array(''), meaning using the default schema (an empty string refers to the
     * default schema). This property is mainly used when turning on and off integrity checks
     * so that factory data can be populated into the database without causing problem.
     */
    public $schemas = array('');
    /**
     * @var string the suffix for the factory files where the file name is constructed
     * from the \CActiveRecord class name and the suffix. Eg. by default "UsersFactory" would
     * expect that you create a factory for the "Users" \CActiveRecord model
     */
    public $factoryFileSuffix = 'Factory';

    /**
     * @var \CDbConnection
     */
    protected $_db;
    /**
     * @var FactoryData[] (class name => FactoryData)
     */
    protected $_factoryData;

    /**
     * factory files
     *
     * @var array
     */
    private static $files = array();

    /**
     * _basePath
     *
     * @var string
     */
    private static $_basePath = null;

    /**
     * Initializes this application component.
     */
    public function init()
    {
        parent::init();
        if ($this->basePath) {
            self::$_basePath = \Yii::getPathOfAlias($this->basePath);
        }
        $this->prepare();
    }

    /**
     * Returns the database connection used to load factories.
     * @throws \CException if {@link connectionID} application component is invalid
     * @return \CDbConnection the database connection
     */
    public function getDbConnection()
    {
        if ($this->_db === null) {
            $this->_db = \Yii::app()->getComponent($this->connectionID);
            if (!$this->_db instanceof \CDbConnection) {
                throw new \CException(\Yii::t(self::LOG_CATEGORY, '\YiiFactoryGirl\Factory.connectionID "{id}" is invalid. Please make sure it refers to the ID of a CDbConnection application component.',
                    array('{id}' => $this->connectionID)));
            }
        }
        return $this->_db;
    }

    /**
     * Prepares the factories for the whole test.
     * This method is invoked in {@link init}. It executes the database init script
     * if it exists. Otherwise, it will load all available factories.
     */
    public function prepare()
    {
        $initFile = self::getBasePath() . DIRECTORY_SEPARATOR . $this->initScript;

        $this->checkIntegrity(false);

        if (is_file($initFile)) {
            require($initFile);
        } else {
            $factoryData = $this->loadFactoryData();
            foreach ($factoryData as $className => $factory) {
                $this->resetTable($factory->tableName);
            }
        }
        $this->checkIntegrity(true);
    }

    /**
     * Clean up the database records created by calling Factory->create()
     * Should be called in tearDown() method in tests to avoid side effects.
     */
    public function flush()
    {
        $factoryData = $this->loadFactoryData();
        foreach ($factoryData as $className => $factory) {
            $this->resetTable($factory->tableName);
        }
        Sequence::resetAll();
    }

    /**
     * Resets the table to the state that it contains data.
     * If there is an init script named "tests/factories/TableName.init.php",
     * the script will be executed.
     * Otherwise, {@link truncateTable} will be invoked to delete all rows in the table
     * and reset primary key sequence, if any.
     * @param string $tableName the table name
     */
    public function resetTable($tableName)
    {
        $initFile = self::getBasePath() . DIRECTORY_SEPARATOR . $tableName . $this->initScriptSuffix;
        if (is_file($initFile)) {
            require($initFile);
        } else {
            $this->truncateTable($tableName);
        }
    }

    /**
     * Returns the information of the available factories.
     * This method will search for all PHP files under {@link basePath}.
     * If a file's name is the same as a table name, it is considered to be the factories data for that table.
     * @return FactoryData[] the information of the available factories (class name => FactoryData)
     * @throw FactoryException if there is a misbehaving file in the factory data files path
     */
    protected function loadFactoryData()
    {
        if ($this->_factoryData === null) {
            $this->_factoryData = array();
            $suffixLen = strlen($this->initScriptSuffix);
            foreach (self::getFiles() as $path) {
                if (substr(end(explode(DIRECTORY_SEPARATOR, $path)), -$suffixLen) !== $this->initScriptSuffix) {
                    $data = FactoryData::fromFile($path, "{$this->factoryFileSuffix}.php");
                    $this->_factoryData[$data->className] = $data;
                }
            }
        }
        return $this->_factoryData;
    }

    /**
     * Enables or disables database integrity check.
     * This method may be used to temporarily turn off foreign constraints check.
     * @param boolean $check whether to enable database integrity check
     */
    public function checkIntegrity($check)
    {
        foreach ($this->schemas as $schema) {
            $this->getDbConnection()->getSchema()->checkIntegrity($check, $schema);
        }
    }

    /**
     * Removes all rows from the specified table and resets its primary key sequence, if any.
     * You may need to call {@link checkIntegrity} to turn off integrity check temporarily
     * before you call this method.
     * @param string $tableName the table name
     * @throws \CException if given table does not exist
     */
    public function truncateTable($tableName)
    {
        $db = $this->getDbConnection();
        $schema = $db->getSchema();
        if (($table = $schema->getTable($tableName)) !== null) {
            $db->createCommand('DELETE FROM ' . $table->rawName)->execute();
            $schema->resetSequence($table, 1);
        } else {
            throw new \CException("Table '$tableName' does not exist.");
        }
    }

    /**
     * Truncates all tables in the specified schema.
     * You may need to call {@link checkIntegrity} to turn off integrity check temporarily
     * before you call this method.
     * @param string $schema the schema name. Defaults to empty string, meaning the default database schema.
     * @see truncateTable
     */
    public function truncateTables($schema = '')
    {
        $tableNames = $this->getDbConnection()->getSchema()->getTableNames($schema);
        foreach ($tableNames as $tableName) {
            $this->truncateTable($tableName);
        }
    }

    /**
     * Returns \CActiveRecord instance that is not yet saved.
     * @param $class
     * @param array $args
     * @param null $alias
     * @return \CActiveRecord
     * @throws FactoryException
     */
    public function build($class, array $args = array(), $alias = null)
    {
        /* @var $obj \CActiveRecord */
        $obj = $this->instanciate($class);
        $reflection = new \ReflectionObject($obj);
        $factory = $this->getFactoryData($class);
        $attributes = $factory->getAttributes($args, $alias);
        foreach ($attributes as $key => $value) {
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);
                $property->setAccessible(true);
                $property->isStatic() ? $property->setValue($value) : $property->setValue($obj, $value);
            } else {
                try {
                    $obj->__set($key, $value);
                } catch(\CException $e) {
                    \Yii::log($e->getMessage(), \CLogger::LEVEL_ERROR);
                    throw new FactoryException(\Yii::t(self::LOG_CATEGORY, 'Unknown attribute "{attr} for class {class}.', array(
                        '{attr}' => $key,
                        '{class}' => $class
                    )));
                }
            }
        }

        return $obj;
    }

    /**
     * Returns \CActiveRecord instance that is saved.
     * @param $class
     * @param array $args
     * @param null $alias
     * @return \CActiveRecord
     */
    public function create($class, array $args = array(), $alias = null)
    {
        $obj = $this->build($class, $args, $alias);

        $schema = $this->getDbConnection()->getSchema();
        $builder = $schema->getCommandBuilder();
        $table = $schema->getTable($obj->tableName());

        // make sure it gets inserted
        $this->checkIntegrity(false);

        // attributes to insert
        $attributes = $obj->getAttributes();
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

        // re-enable foreign key check state
        $this->checkIntegrity(true);
        $obj->setScenario('update');
        $obj->setIsNewRecord(false);
        return $obj;
    }

    /**
     * Returns array of attributes that can be set to a \CActiveRecord model
     * @param $class
     * @param $args
     * @param $alias
     * @return array
     */
    public function attributes($class, array $args = array(), $alias)
    {
        return $this->getFactoryData($class)->getAttributes($args, $alias);
    }

    /**
     * @param string $class
     * @return FactoryData|false
     * @throws FactoryException
     */
    public function getFactoryData($class) {
        if (!isset($this->_factoryData[$class])) {
            $this->instanciate($class);
            $this->_factoryData[$class] = new FactoryData($class);
        }
        return $this->_factoryData[$class];
    }

    /**
     * instanciate
     *
     * @param string $class
     * @return \CActiveRecord
     * @throws FactoryException
     */
    private function instanciate($class)
    {
        try {
            $obj = new $class;
            if (!($obj instanceof \CActiveRecord)) {
                // trigger, catch and rethrow proper error
                throw new \CException("{$class} is not CActiveRecord.");
            }
        } catch (\CException $e) {
            throw new FactoryException(\Yii::t(self::LOG_CATEGORY, $e->getMessage()));
        } catch (\Exception $e) {
            throw new FactoryException(\Yii::t(self::LOG_CATEGORY, 'There is no {class} class loaded.', array(
                '{class}' => $class,
            )));
        }

        return $obj;
    }

    /**
     * getFiles
     *
     * @param bool $absolute
     * @return array
     */
    public static function getFiles($absolute = true)
    {
        if (!self::$files) {
            self::$files = \CFileHelper::findFiles(self::getBasePath(), array('absolutePaths' => true));
        }

        return $absolute ? self::$files : array_map(function($file) {
            return end(explode(DIRECTORY_SEPARATOR, $file));
        }, self::$files);
    }

    /**
     * getBasePath
     *
     * @return string
     */
    public static function getBasePath()
    {
        if (!self::$_basePath) {
            self::$_basePath = \Yii::getPathOfAlias('application.tests.factories');
        }
        return self::$_basePath;
    }
}
