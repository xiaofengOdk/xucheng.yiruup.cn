<?php

namespace app\bootstrap;

use Webman\Bootstrap;

//多进程 并发运行计划任务 防止挤压
class Crontab1 implements Bootstrap
{
    public static function start($worker)
    {
        // 是否是命令行环境 ?
        $is_console = !$worker;
        if ($is_console) {
            // 如果你不想命令行环境执行这个初始化，则在这里直接返回
            return;
        }

        if ($worker->id === 1) {
            echo "worker_name" . $worker->name . "worker_id is" . $worker->id . PHP_EOL;
            //日加粉量 到量提醒 每五分钟执行一次
            \Workerman\Timer::add(300, function () {
                require app_path() . '/crontab/ConversionsDay.php';
            });
            //账户被拒 被禁用 发企微通知
            \Workerman\Timer::add(280, function () {
                require app_path() . '/crontab/SubuserStat.php';
            });
            ////账户被拒 被禁用 发企微通知
        }
        if ($worker->id === 2) {
            //如果今日有消费，更新 信息流表的物料状态为正常投放
            \Workerman\Timer::add(600, function () {
                require app_path() . '/crontab/MaterialSchedule1.php';
            });
            //项目预算，企业微信通知。每五分钟执行一次
            \Workerman\Timer::add(302, function () {
                require app_path() . '/crontab/ProjectBalance.php';
            });


        }

        if ($worker->id === 3) {
            //到店量拉停 每10分钟一次
            \Workerman\Timer::add(609, function () {
                require app_path() . '/crontab/Storenumer.php';
            });
        }
        if ($worker->id === 4) {
            //备款通知 每10分钟一次
            \Workerman\Timer::add(616, function () {
                require app_path() . '/crontab/Beikuanmessage.php';
            });
        }
        if ($worker->id === 5) {
            //创意过审状态。每10执行一次
            \Workerman\Timer::add(590, function () {
                require app_path() . '/crontab/CronCreatives.php';
            });
        }
    }

}