<?php

namespace YiiFactoryGirl;

class Factory extends \CApplicationComponent
{
    const LOG_CATEGORY = 'yii-factory-girl';

    const INIT_SCRIPT_SUFFIX = 'init.php';

    const FACTORY_FILE_SUFFIX = 'Factory';

    const FACTORY_METHOD_SUFFIX = 'Factory';

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
     * db
     *
     * @var YiiFactoryGirl\Db
     */
    protected $db;

    /**
     * static properties
     */
    protected static $_files = array();
    protected static $_basePath = null;
    protected static $_builders = array();
    protected static $_factoryMethods = null;
    protected static $_reflectionMethods = null;
    protected static $_callable = null;

    /**
     * Initializes this application component.
     */
    public function init()
    {
        parent::init();
        if ($this->basePath) {
            self::$_basePath = \Yii::getPathOfAlias($this->basePath);
        }
        $this->db = new Db($this->connectionID, $this->schemas);
        $this->setBuilders();

        $this->prepare();
    }

    /**
     * getDbConnection
     *
     * @return \CDbConnection
     * @see YiiFactoryGirl\Db::getConnection
     */
    public function getDbConnection()
    {
        return $this->db->getConnection();
    }

    /**
     * getDb
     *
     * @return YiiFactoryGirl\Db
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Prepares the factories for the whole test.
     * This method is invoked in {@link init}. It executes the database init script
     * if it exists. Otherwise, it will load all available factories.
     */
    public function prepare()
    {
        if ($initFile = self::getFilePath('init')) {
            require($initFile);
        } else {
            $this->flush();
        }
    }

    /**
     * Clean up the database records created by calling Factory->create()
     * Should be called in tearDown() method in tests to avoid side effects.
     */
    public function flush()
    {
        $this->db->checkIntegrity(false);
        foreach (self::$_builders as $builder) {
            if ($builder->isActiveRecord()) {
                $this->resetTable($builder->getTableName());
            }
        }
        $this->db->checkIntegrity(true);
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
        if ($initFile = self::getFilePath($tableName, $this->initScriptSuffix)) {
            require($initFile);
        } else {
            $this->db->truncateTable($tableName);
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
        return $this->getBuilder($class)->build($args, $alias);
    }

    /**
     * Returns \CActiveRecord instance that is saved.
     * @param $class
     * @param array $args
     * @param null $alias
     * @return \CActiveRecord
     * @throws YiiFactoryGirl\FactoryException
     */
    public function create($class, array $args = array(), $alias = null)
    {
        $builder = $this->getBuilder($class);
        if (!$builder->isActiveRecord()) {
            throw new FactoryException($class . ' is not ActiveRecord.');
        }
        $builder->build($args, $alias);
        return Creator::create($builder->getFactoryData()->build, $builder->getFactoryData()->relations);
    }

    /**
     * getFiles
     *
     * @param bool $absolute
     * @return array
     */
    public static function getFiles($absolute = true)
    {
        if (!self::$_files) {
            self::$_files = \CFileHelper::findFiles(self::getBasePath(), array('absolutePaths' => true));
        }

        return $absolute ? self::$_files : array_map(function($file) {
            $tmp = explode(DIRECTORY_SEPARATOR, $file);
            return end($tmp);
        }, self::$_files);
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

    /**
     * getFilePath
     *
     * @param string $fileName
     * @param string $suffix
     * @return string|false
     */
    public static function getFilePath($fileName, $suffix = '.php')
    {
        $basePath = self::getBasePath() . DIRECTORY_SEPARATOR;
        $files = self::getFiles();
        $file = $basePath.$fileName.$suffix;
        return in_array($file, $files) && file_exists($file) ? $file : false;
    }

    /**
     * getBuilder
     *
     * @param string $class
     * @return YiiFactoryGirl\Builder
     */
    public function getBuilder($class)
    {
        if (!isset(self::$_builders[$class])) {
            self::$_builders[$class] = new Builder($class);
        }
        return self::$_builders[$class];
    }

    /**
     * setBuilders
     *
     * @return void
     */
    private function setBuilders()
    {
        $suffixLen = strlen(self::INIT_SCRIPT_SUFFIX);
        foreach (self::getFiles(false) as $fileName) {
            if (substr($fileName, -$suffixLen) !== self::INIT_SCRIPT_SUFFIX) {
                $class = strtr($fileName, array(self::FACTORY_FILE_SUFFIX.'.php' => ''));
                self::$_builders[$class] = new Builder($class);
            }
        }
    }

    /**
     * getComponent
     *
     * If not set factorygirl component, this method set it.
     * component name is expected as `factorygirl`.
     * @return YiiFactoryGirl\Factory
     */
    public static function getComponent()
    {
        if (!\Yii::app()->hasComponent('factorygirl')) {
            \Yii::app()->setComponent('factorygirl', array('class' => 'YiiFactoryGirl\Factory'));
        }

        return \Yii::app()->factorygirl;
    }

    /**
     * setFactoryMethods
     *
     * @return void
     */
    private static function setFactoryMethods()
    {
        self::$_factoryMethods = array_map(function($path) {
            return explode('.', $path)[0];
        }, self::getComponent()->getFiles(false));
    }

    /**
     * setReflectionMethods
     *
     * @return void
     */
    private static function setReflectionMethods()
    {
        self::$_reflectionMethods = array_map(
            function($method) { return $method->name; },
            array_merge(
                (new \ReflectionClass('YiiFactoryGirl\Db'))->getMethods(\ReflectionMethod::IS_PUBLIC),
                (new \ReflectionClass('YiiFactoryGirl\Factory'))->getMethods(\ReflectionMethod::IS_PUBLIC)
        ));
    }

    /**
     * isCallable
     *
     * @param string $name
     * @return bool
     */
    public static function isCallable($name)
    {
        if (is_null(self::$_callable)) {
            self::setFactoryMethods();
            self::setReflectionMethods();
            self::$_callable = array_merge(self::$_factoryMethods, self::$_reflectionMethods);
        }

        if(in_array($name, self::$_callable)) {
            return true;
        }

        if (preg_match('/(.*)'.self::FACTORY_METHOD_SUFFIX.'$/', $name, $match)) {
            try {
                // TODO when not ActiveRecord
                $reflection = new \ReflectionClass($match[1]);
                if ($reflection->isSubclassOf('\CActiveRecord')) {
                    self::$_factoryMethods[] = $name;
                    self::$_callable[] = $name;
                    return true;
                }
            } catch (\Exception $e) {
                //do nothing
            }
        }

        return false;
    }

    /**
     * __call
     *
     * This method emulates factory method
     * if called-method format is '{:ModelName}Factory'.
     *
     * @param string $name method name
     * @param array $args
     * @return mixed
     * @throws YiiFactoryGirl\FactoryException
     */
    public function __call($name, $args)
    {
        if (!self::isCallable($name)) {
            throw new FactoryException('Undefined method call: ' . $name);
        }

        if (is_callable(array($this->db, $name))) {
            return call_user_func_array(array($this->db, $name), $args);
        }

        if (in_array($name, self::$_factoryMethods)) {
            $class = str_replace(self::FACTORY_METHOD_SUFFIX, '', $name);
            $attr  = isset($args[0]) ? $args[0] : array();
            $alias = isset($args[1]) ? $args[1] : null;
            return $this->create($class, $attr, $alias);
        }

        return parent::__call($name, $args);
    }
}
