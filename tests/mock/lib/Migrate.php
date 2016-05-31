<?php
/**
 * Migrate
 *
 * @see \CApplicationComponent
 */
class Migrate extends \CApplicationComponent
{
    /**
     * tables
     *
     * @var array
     */
    private $tables = array(
        'HaveNoRelation' => array('id' => 'int PRIMARY KEY AUTO_INCREMENT', 'name' => 'varchar(255)'),
        'Author'         => array('id' => 'int PRIMARY KEY AUTO_INCREMENT', 'name' => 'varchar(255)'),
        'Book'           => array('id' => 'int PRIMARY KEY AUTO_INCREMENT', 'name' => 'varchar(255)', 'Author_id' => 'int', 'Series_id' => 'int'),
        'Colophon'       => array('id' => 'int PRIMARY KEY AUTO_INCREMENT', 'Book_id' => 'int', 'Publisher_id' => 'int'),
        'Publisher'      => array('id' => 'int PRIMARY KEY AUTO_INCREMENT', 'name' => 'varchar(255)'),
        'Series'         => array('id' => 'int PRIMARY KEY AUTO_INCREMENT', 'name' => 'varchar(255)', 'Publisher_id' => 'int'),
        'SameIdToAuthor' => array('id' => 'int PRIMARY KEY AUTO_INCREMENT', 'name' => 'varchar(255)'),
    );

    /**
     * foreignKey
     *
     * @var array
     */
    private $foreignKey = array(
        'fk_Book_Author'        => array('Book', 'Author_id', 'Author', 'id'),
        'fk_Book_Series'        => array('Book', 'Series_id', 'Series', 'id'),
        'fk_Colophon_Book'      => array('Colophon', 'Book_id', 'Book', 'id'),
        'fk_Colophon_Publisher' => array('Colophon', 'Publisher_id', 'Publisher', 'id'),
        'fk_Author_SameId'      => array('SameIdToAuthor', 'id', 'Author', 'id')
    );

    /**
     * up
     *
     * @return void
     */
    public function up()
    {
        $db = Yii::app()->db;
        $db->getSchema()->checkIntegrity(false);
        foreach ($this->tables as $tbl => $columns) {
            if ($this->tableExists($tbl)) {
                $db->createCommand()->dropTable($tbl);
            }
            $db->createCommand()->createTable($tbl, $columns);
        }
        $db->getSchema()->checkIntegrity(true);
        foreach ($this->foreignKey as $name => $foreignKey) {
            list($table, $column, $references, $refColumn) = $foreignKey;
            $db->createCommand()->addForeignKey($name, $table, $column, $references, $refColumn);
        }
    }

    /**
     * down
     *
     * @return void
     */
    public function down()
    {
        $db = Yii::app()->db;
        $db->getSchema()->checkIntegrity(false);
        foreach ($this->tables as $tbl => $columns) {
            if ($this->tableExists($tbl)) {
                $db->createCommand()->dropTable($tbl);
            }
        }
        $db->getSchema()->checkIntegrity(true);
    }

    /**
     * tableExists
     *
     * @param string $tableName
     * @return bool
     */
    public function tableExists($tableName)
    {
        return in_array($tableName, Yii::app()->db->schema->tableNames);
    }
}
