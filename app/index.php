<?php
// change the following paths if necessary
$yii=dirname(__FILE__).'/../framework/yii.php';
$config=dirname(__FILE__).'/protected/config/main.php';
require_once($yii);
date_default_timezone_set($config['timezone']);
Yii::createWebApplication($config)->run();
