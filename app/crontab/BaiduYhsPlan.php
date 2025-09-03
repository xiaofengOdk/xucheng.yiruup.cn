<?php
//优化师日报 企微推送 每天下午 15点 30推送
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
$date = date('Y-m-d');

$plan = Db::connection('mysql2')->table('baidu_yhs_plan')
    ->select('id', 'dailyrecord', 'admin_id', 'eventDate', 'status')
    ->where('eventDate', $date)
    ->orderBy('id', 'asc')->get();
$p1 = [];
foreach ($plan as $p) {
    $status_s = $p->status == 2
        ? '<font color="warning"> 未完成 </font>'
        : '<font color="comment"> 已完成 </font>';

    // 初始化为 ''，然后直接追加
    if (!isset($p1[$p->admin_id]['weixin_message'])) {
        $p1[$p->admin_id]['weixin_message'] = '';
    }

    $p1[$p->admin_id]['weixin_message'] .=
        '>计划：<font color="info">' . $p->dailyrecord . '</font> 状态：' . $status_s . "\n";
}

foreach ($p1 as $k => $p) {
    //是否提醒过
    $is_push = Redis::get('baidu_yhs_plan_' . $date . $k);
    if (!$is_push) {
        $weixin_message = "优化师 <font color=\"info\">" . $admins[$k]->userName . "</font>\n"
            . $p['weixin_message'] .
            ">时间: " . date("Y-m-d H:i:s") . "。\n";
        if (isset($admins[$k])) {
            $weixin_message .= "<@" . $admins[$k]->weixin . ">";
        }
       // var_dump($weixin_message);
        if (isset($admins[$k]->weixin_push) && $admins[$k]->weixin_push != null)
            push_work_weixin($admins[$k]->weixin_push, $weixin_message);
        $workweixin = push_work_weixin('4742b357-8c97-4f7c-9702-86c7986cdf9c', $weixin_message);
        //只提醒一次
        if (is_array($workweixin) && $workweixin['errcode'] == 0)
            Redis::set('baidu_yhs_plan_' . $date . $k, 1);
    }
};
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
Log::channel('subuserstat')->info('优化师日报 企微推送等数据完毕,BaiduYhsPlan.php 代码运行时间: ' . $executionTime . "秒");
