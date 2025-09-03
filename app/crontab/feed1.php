<?php
//信息流计划复制删除
namespace app\crontab;

use support\Db;
use support\Log;


DB::table('baidu_xinxiliu_feed')->where('status', 0)->chunkById(30, function ($feed) {
    // 记录开始时间
    $startTime = microtime(true);
    foreach ($feed as $item) {
        //当前时间
        $now = time();
        //第一次复制时间
        $repTime = strtotime($item->repTime);
        //上一次执行成功时间
        $lastRepTime = strtotime($item->lastRepTime);
        //间隔多少小时运行
        $apartHours = $item->apartHours;
        echo '上一次执行成功时间' . $lastRepTime . '当前时间:' . $now . '._________.' . $repTime . PHP_EOL;
        echo '当前时间:' . date('Y-m-d H:i:s') . '___复制时间：' . $item->repTime . '两者间差：' . $now - $repTime . '秒', PHP_EOL;
        $run = 0;
        //第一次运行
        if ($item->lastRepTime == null) {
            if ($now - $repTime > 0) {
                $run = 1;
            }
        } else {  //执行成功一次之后 只需要判断当前时间 跟上一次执行成功时间$lastRepTime的时间间隔
            if ($now - $lastRepTime > ($apartHours * 3600)) {
                $run = 1;
            }
        }
        if ($run == 1) {
            $userName = trim($item->subName);
            //新建创意
            require app_path() . '/crontab/feed.php';
            DB::table('baidu_xinxiliu_feed')
                ->where('id', $item->id)
                ->update([
                    'lastRepTime' => date("Y-m-d H:i:s", time()),
                    'updated_at' => date("Y-m-d H:i:s", time()),
                ]);
            echo '账户' . $item->subName . '复制成功' . PHP_EOL;
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            Log::channel('feed')->info('账户：' . $item->subName . ' 计划复制成功,代码运行时间: ' . $executionTime . "秒");
        }
    }
});