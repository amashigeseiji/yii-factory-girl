<?php
/**
 * YiiFactoryGirl\Db
 *
 * @author Seiji Amashige <tenjuu99@gmail.com>
 * @package YiiFactoryGirl
 */

namespace YiiFactoryGirl;

/**
 * Db
 *
 * @property $connectionID
 * @property $schemas
 * @property $db
 * @property $tables
 */
class Db
{
    /**
     * connectionID
     *
     * @var string
     */
    private $connectionID;

    /**
     * schemas
     *
     * @var array
     */
    private $schemas;

    /**
     * db
     *
     * @var \CDbConnection
     */
    private $db;

    /**
     * tables
     *
     * @var array
     */
    private $tables;

    /**
     * __construct
     *
     * @param string $connectionID
     * @param array $schemas
     * @return void
     */
    public function __construct($connectionID, $schemas = array())
    {
        $this->connectionID = $connectionID;
        $this->schemas = $schemas;
    }

    /**
     * Returns the database connection used to load factories.
     * @throws \CException if {@link connectionID} application component is invalid
     * @return \CDbConnection the database connection
     */
    public function getConnection()
    {
        if (!$this->db) {
            $db = \Yii::app()->getComponent($this->connectionID);
            if (!$db instanceof \CDbConnection) {
                throw new \CException(\Yii::t(Factory::LOG_CATEGORY, '\YiiFactoryGirl\Db.connectionID "{id}" is invalid. Please make sure it refers to the ID of a CDbConnection application component.',
                    array('{id}' => $this->connectionID)));
            }
            $this->db = $db;
        }
        return $this->db;
    }

    /**
     * Clean up the database records created by calling Factory->create()
     * Should be called in tearDown() method in tests to avoid side effects.
     */
    public function flush()
    {
        $this->checkIntegrity(false);
        foreach ($this->tables as $tbl) {
            $this->truncateTable($tbl);
        }
        $this->checkIntegrity(true);
    }

    /**
     * Enables or disables database integrity check.
     * This method may be used to temporarily turn off foreign constraints check.
     * @param boolean $check whether to enable database integrity check
     */
    public function checkIntegrity($check)
    {
        foreach ($this->schemas as $schema) {
            $this->getConnection()->getSchema()->checkIntegrity($check, $schema);
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
        $schema = $this->getConnection()->getSchema();
        if (($table = $schema->getTable($tableName)) !== null) {
            $this->getConnection()->createCommand()->truncateTable($table->name);
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
        $tableNames = $this->getConnection()->getSchema()->getTableNames($schema);
        foreach ($tableNames as $tableName) {
            $this->truncateTable($tableName);
        }
    }

    /**
     * addTable
     *
     * @param string $tableName
     * @return void
     */
    public function addTable($tableName)
    {
        $this->tables[] = $tableName;
    }
}
