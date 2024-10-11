<?php

// Chargement conf global;
$pathGlobal = getcwd()."/config/autoload/global.php";
$globalConf = array();
if(file_exists($pathGlobal)){
    $globalConf = require $pathGlobal;
}
// Chargement conf local;
$pathLocal = getcwd()."/config/autoload/local.php";
$localConf = array();
if(file_exists($pathLocal)){
    $localConf = require $pathLocal;
}

$globalConf = array_replace_recursive($globalConf,$localConf);
if(empty($localConf['doctrine']['connection']['orm_default']['params'])){
    die("Connection parameters not configured");
}

return array(
    'paths' => array(
        'migrations' => __DIR__.'/db',
        'seeds' => __DIR__.'/seeds',
    ),
    'environments' => array(
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'common',
        'common' => array(
            'adapter' => 'mysql',
            'host' => $globalConf['doctrine']['connection']['orm_default']['params']['host'],
            'name' => $globalConf['doctrine']['connection']['orm_default']['params']['dbname'],
            'user' => $globalConf['doctrine']['connection']['orm_default']['params']['user'],
            'pass' => $globalConf['doctrine']['connection']['orm_default']['params']['password'],
            'port' => $globalConf['doctrine']['connection']['orm_default']['params']['port'],
            'charset' => 'utf8mb4',
        ),
    ),
);
