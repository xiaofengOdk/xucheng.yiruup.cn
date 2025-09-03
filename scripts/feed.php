<?php
//计划
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../support/bootstrap.php';
use Workerman\Worker;
$worker = new Worker();
$worker->onWorkerStart = function(){

    require app_path() . '/crontab/feed2.php';
};
Worker::runAll();
