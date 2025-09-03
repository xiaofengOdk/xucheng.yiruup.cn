<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../support/bootstrap.php';
use Workerman\Worker;
$worker = new Worker();
$worker->onWorkerStart = function(){

    //require app_path() . '/crontab/RefreshToken.php';
    //require app_path() . '/crontab/XinxiliuSubuser.php';
    //require app_path() . '/crontab/ConversionsDay.php';
    //require app_path() . '/crontab/SubuserStat.php';
    //require app_path() . '/crontab/BaiduYhsPlan.php';
    require app_path() . '/crontab/CronCreatives.php';



};
Worker::runAll();
