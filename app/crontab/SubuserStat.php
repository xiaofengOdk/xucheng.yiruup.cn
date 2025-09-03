<?php
//账户被拒 被禁用 发企微通知 280秒运行一次
use support\Db;
use support\Log;
use support\Redis;

// 记录开始时间
$startTime = microtime(true);
$admins = [];
$admins1 = Db::connection('mysql2')->table('wa_admins')
    ->select('id', 'userName', 'weixin', 'sweixin', 'weixin_push')
    ->where('status', null)
    ->get()->toArray();
foreach ($admins1 as $admin) {
    $admins[$admin->id] = $admin;
}

Db::connection('mysql2')->table('baidu_xinxiliu_subuser')
    //->whereIn('userStat', [4, 7])
    ->orderBy('id', 'desc')->chunkById(50, function ($baidu_xinxiliu_subuser) use ($admins) {
        foreach ($baidu_xinxiliu_subuser as $subuser) {


            if ($subuser->userStat == 4 || $subuser->userStat == 7) {
                $s = '被拒绝';
                if ($subuser->userStat == 7)
                    $s = '被禁用';
                //是否提醒过
                $is_push = Redis::get('subuserstat_' . $subuser->id);
                if (!$is_push) {
                    echo $subuser->userName . '__subuserstat_' . $subuser->id . PHP_EOL;
                    $project= Db::connection('mysql2')->table('baidu_xinxiliu_project')
                        ->select('clientName')
                        ->where('subName', $subuser->userName)
                        ->first();
                    $weixin_message='';
                    if($project)
                        $weixin_message.="项目：<font color=\"info\">" . $project->clientName . " </font>。\n";
                    $weixin_message.= "账号：<font color=\"info\">" . $subuser->userName . "</font>
                                             >账户状态：<font color=\"warning\"> " . $s . " </font>
                                             >请<font color=\"info\">相关同事注意</font>。
                                             >时间: " . date("Y-m-d H:i:s") . "。\n";
                    if ($subuser->adminId > 0 && isset($admins[$subuser->adminId])) {
                        $weixin_message .= ">优化师: <font color=\"info\">" . $admins[$subuser->adminId]->userName . "</font>。\n";
                        $weixin_message .= "<@" . $admins[$subuser->adminId]->weixin . ">";
                    }
                    if (isset($admins[$subuser->adminId]->weixin_push) && $admins[$subuser->adminId]->weixin_push != null)
                        push_work_weixin($admins[$subuser->adminId]->weixin_push, $weixin_message);
                    $workweixin = push_work_weixin('4742b357-8c97-4f7c-9702-86c7986cdf9c', $weixin_message);
                    //只提醒一次
                    if (is_array($workweixin) && $workweixin['errcode'] == 0)
                        Redis::set('subuserstat_' . $subuser->id, 1);
                    //给梅子提醒
                    $workweixin = push_work_weixin('240f8c3c-bab0-4a8e-8902-f8c5498c365a', $weixin_message);
                    $workweixin = push_work_weixin('a8d89e6d-40bc-4e04-93ff-761cbba41c06', $weixin_message);
                }
            } else {
                Redis::del('subuserstat_' . $subuser->id);
            }
        }
    });
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
Log::channel('subuserstat')->info('账户被拒 被禁用 发企微通知等数据完毕,SubuserStat.php 代码运行时间: ' . $executionTime . "秒");
