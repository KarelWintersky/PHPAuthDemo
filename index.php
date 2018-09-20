<?php
/**
 * User: Karel Wintersky
 * Date: 19.09.2018, time: 17:25
 */

require_once 'vendor/autoload.php';
require_once 'engine/routing.helpers.php';

require_once 'engine/routing.rules.php';

use Pecee\SimpleRouter\SimpleRouter;

$config = [
    'adapter'   =>  'mysql',
    'hostname'  =>  'localhost',
    'username'  =>  'phpauthdemo',
    'password'  =>  'password',
    'database'  =>  'phpauthdemo',
    'charset'   =>  'utf8',
    'port'      =>  3306
];

DB::init(NULL, $config);

$dbh = DB::getConnection();

// use PHPAuth\Config as PHPAuthConfig;
// use PHPAuth\Auth as PHPAuth;

// $config = new PHPAuthConfig($dbh);
// $auth = new PHPAuth($dbh, $config);

SimpleRouter::start();

 
