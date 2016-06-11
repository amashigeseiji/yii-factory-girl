<?php

$path = function() {
    return implode(DIRECTORY_SEPARATOR, array_merge([dirname(__DIR__)], func_get_args()));
};

require_once($path('vendor', 'autoload.php'));
require_once($path('vendor', 'yiisoft', 'yii', 'framework', 'yiit.php'));
require_once($path('tests', 'lib', 'YiiFactoryGirl_Unit_TestCase.php'));

Yii::createWebApplication(array(
    'basePath'  => $path('tests', 'mock'),
    //'preload'   => array('log'),
    'import'    => array('application.models.*', 'application.components.*', 'application.form.*'),
    'components'=> array(
        //'factorygirl' => array(
        //    'class' => 'YiiFactoryGirl\Factory',
        //),
        'db'    => array(
            // @NOTE Make sure database does exist before run test.
            // Rewrite configuration if needed.
            'connectionString' => 'mysql:host=localhost;dbname=yii_factorygirl_test',
            'username' => 'root',
            'password' => '',
        ),
        'migrate' => array(
            'class' => 'application.lib.migrate'
        )
    ),
));
Yii::app()->migrate->up();
