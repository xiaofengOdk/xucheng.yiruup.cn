<?php
//如果今日有消费，更新 信息流表的物料状态为正常投放
use support\Db;
use support\Log;

// 记录开始时间
$startTime = microtime(true);

Db::connection('mysql2')->table('baidu_xinxiliu_reportdata')
    ->select('id', 'userId', 'impression', 'click', 'cost')
    ->where('eventDate', date('Y-m-d'))
    ->where('cost', '>', 0)
    ->orderBy('id', 'desc')->chunkById(50, function ($reportdata) {
        foreach ($reportdata as $reportdata1) {
            $subUser = Db::connection('mysql2')->table('baidu_xinxiliu_subuser')
                ->select('userName', 'userId')
                ->where('userId', $reportdata1->userId)->first();
            $project = Db::connection('mysql2')->table('baidu_xinxiliu_project')
                ->select('subName', 'materialSchedule1')
                ->where('subName', $subUser->userName)->first();
            if ($project && ($project->materialSchedule1 == 2 || $project->materialSchedule1 == 3))
                 Db::connection('mysql2')->table('baidu_xinxiliu_project')
                    ->where('subName', $subUser->userName)
                    ->update(['materialSchedule1' => 1]);
        }
    });
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
Log::channel('subuserstat')->info('信息流表的物料状态,MaterialSchedule1.php 代码运行时间: ' . $executionTime . "秒");
