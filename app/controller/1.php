<?php
//项目日预算 企微提醒
namespace app\crontoller;

use support\Db;
use support\Log;
use support\Redis;
use plugin\admin\app\model\CampaginBaidu;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../support/bootstrap.php';

// 记录开始时间
$startTime = microtime(true);
//CampaginBaidu
$response = new  CampaginBaidu();
$startsTime =  date('Y-m-d H:i:s', strtotime('+1 days'));
$endsTime =   date('Y-m-d H:i:s', strtotime('+3 months'));

$admins = [];
$admins1 = Db::table('wa_admins')
    ->select('id', 'userName', 'weixin', 'sweixin', 'weixin_push')
    ->where('status', null)
    ->get()->toArray();
foreach ($admins1 as $admin) {
    $admins[$admin->id] = $admin;
}
DB::table('baidu_xinxiliu_project_list')->select('id', 'projectName', 'project_balance', 'conversions_day')
    ->where('project_balance', '>', 0)
    ->where('id',69)
    ->chunkById(30, function ($project_list) use ($admins,$response,$startsTime,$endsTime) {
        foreach ($project_list as $project_list_item) {
            $project = DB::table('baidu_xinxiliu_project')
            ->leftJoin('baidu_xinxiliu_subuser', 'baidu_xinxiliu_project.subName', '=', 'baidu_xinxiliu_subuser.userName')
            ->leftJoin('baidu_xinxiliu_refreshToken', 'baidu_xinxiliu_subuser.masterUid', '=', 'baidu_xinxiliu_refreshToken.userId')  // 关联refreshToken表
            ->select(
                'baidu_xinxiliu_project.id',
                'baidu_xinxiliu_project.clientName',
                'baidu_xinxiliu_project.sellId',
                'baidu_xinxiliu_project.youhuashiId',
                'baidu_xinxiliu_subuser.userId',
                'baidu_xinxiliu_subuser.masterUid',
                'baidu_xinxiliu_subuser.userName',
                'baidu_xinxiliu_subuser.id as sid',
                'baidu_xinxiliu_subuser.budget',
                'baidu_xinxiliu_subuser.userStat',
                'baidu_xinxiliu_refreshToken.accessToken' ,
                'baidu_xinxiliu_refreshToken.expiresTime'  
            )
            ->where('baidu_xinxiliu_project.clientName', $project_list_item->projectName)
            ->where([
                ['baidu_xinxiliu_project.status', '=', 1],
                ['baidu_xinxiliu_project.types', '=', 2]
            ])
            ->orderBy('baidu_xinxiliu_project.id', 'DESC')
            ->get()->toArray();
            $userIds = array_column($project, 'userId');
            $date = date('Y-m-d');
            $reportdata = Db::table('baidu_xinxiliu_reportdata')
                ->select('cost')
                ->where('eventDate', $date)
                ->whereIn('userId', $userIds)
                ->get()->toArray();
            $cost = array_sum(array_column($reportdata, 'cost'));
        //     if ($cost + 50 >= $project_list_item->project_balance) {   
        //         // var_dump($project);
        //      // 优化：先过滤出有效的项目
        //      $validProjects = array_filter($project, function($item) {
        //          return !empty($item->accessToken) && strtotime($item->expiresTime) - time() > 7200 
        //              && $item->budget == 0 && $item->userStat !== 11;
        //      });
        //     //  var_dump($validProjects);
        //       if (!empty($validProjects)) {
        //          foreach($validProjects as $project_item){
        //              $updatetime = Redis::get('baidu_xinxiliu_project_updatetime_' . $date . $project_item->id);
        //              if(!$updatetime){   
        //                 // 将对象转换为数组，给CampaginBaidu模型使用
        //                 $projectArray = (array) $project_item; 
                                                  
        //                   $campaignFeed = $response->getCampaignFeedCrontab([], $projectArray, $project_item->accessToken, null, $startsTime, $endsTime);
        //                   if($campaignFeed['code']==0){
        //                     Redis::set('baidu_xinxiliu_project_updatetime_' . $date . $project_item->id, 1);
        //                     Log::channel('datareportyusuan')->info('百度账号[' . $project_item->userName . '] - 项目[' . $project_item->clientName . ']'.$campaignFeed['message']);
        //                 } else {
        //                     Log::channel('datareportyusuan')->info('百度账号[' . $project_item->userName . '] - 项目[' . $project_item->clientName . ']'.$campaignFeed['message']);

        //                 }
        //              }
        //           }
        //      }
        //  }
        if ($cost + 100 >= $project_list_item->project_balance) {
            //是否提醒过
        //     $is_push = Redis::get('weixin_push_costis_' . $date . $project_list_item->id);
        //    if (!$is_push) {
            $youhuashiIds = array_unique(array_column($project, 'youhuashiId'));

                     $weixinMentions = "";
                     $youhuashiname = "";
                    foreach ($youhuashiIds as $youhuashiId) {
                        if (isset($admins[$youhuashiId])) {
                            $weixinMentions .= "<@" . $admins[$youhuashiId]->weixin . ">";
                            $youhuashiname .= $admins[$youhuashiId]->userName;
                        }
                    }
                    
                $weixin_message = "项目：<font color=\"info\">" . $project_list_item->projectName . "</font> 现消耗为<font color=\"info\">" . $cost . "</font>币。
                                         >快接近于日预算<font color=\"comment\">" . $project_list_item->project_balance . "币</font>。
                                         >请<font color=\"info\">相关同事注意</font>。
                                         >时间: " . date("Y-m-d H:i:s") . "。\n";
                if (isset($admins[$project[0]->sellId]))
                    $weixin_message .= ">销售: <font color=\"info\">" . $admins[$project[0]->sellId]->userName . " </font>。\n";
                if (isset($admins[$project[0]->youhuashiId])) {
                    $weixin_message .= ">优化师: <font color=\"info\">" . $youhuashiname . "</font>。\n";
                    $weixin_message .= $weixinMentions;
                }
                // foreach ($youhuashiIds as $youhuashiId) {
                //     if (isset($admins[$youhuashiId]) && isset($admins[$youhuashiId]->weixin_push) && $admins[$youhuashiId]->weixin_push != null) {
                //         push_work_weixin($admins[$youhuashiId]->weixin_push, $weixin_message);
                //      }
                // }
               
                $workweixin = push_work_weixin('a8d89e6d-40bc-4e04-93ff-761cbba41c06', $weixin_message);
                var_dump($weixin_message);
                // //只提醒一次
                // if (is_array($workweixin) && $workweixin['errcode'] == 0)
                //     Redis::set('weixin_push_costis_' . $date . $project_list_item->id, 1);
        //    }
        }
        }

    });