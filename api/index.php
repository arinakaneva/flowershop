<?php

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

header( 'Access-Control-Allow-Origin: *');
header( 'Access-Control-Allow-Headers: *');
header ('Access-Control-Allow-Methods: *');
if ($_SERVER['REQUEST_METHOD']=='OPTIONS') {
 http_response_code(200);
 header('content-type: application/json');
 exit();}
 
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
