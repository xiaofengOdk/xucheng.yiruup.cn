<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../support/bootstrap.php';

use support\Db;

$project = Db::table('baidu_xinxiliu_project')->get();
foreach ($project as $value){
   $id= Db::table('baidu_xinxiliu_subuser')->where('userName',$value->subName)->update(['adminId'=>$value->youhuashiId]);

    $id>0&&var_dump($id);
}


$hostname = gethostname();
echo "服务器hostname：".$hostname.PHP_EOL;
$serverIp = gethostbyname($hostname);
echo "服务器IP地址：".$serverIp;

