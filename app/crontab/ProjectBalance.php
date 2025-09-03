<?php
//项目日预算 企微提醒

namespace app\crontab;

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
$startsTime =  date('Y-m-d', strtotime('+1 days'));
$endsTime =   date('Y-m-d', strtotime('+3 months'));

$admins = [];
 
$cacheKey = 'wa_admins_cache';
$cachedAdmins = Redis::get($cacheKey);

if ($cachedAdmins) {
    // 将Redis缓存的数据转换为对象格式，保持与数据库查询结果一致
    $adminsArray = json_decode($cachedAdmins, true);
    foreach ($adminsArray as $id => $adminData) {
        $admins[$id] = (object) $adminData;  // 转换为对象
    }
} else {
    $admins1 = Db::connection('mysql2')->table('wa_admins')
        ->select('id', 'userName', 'weixin', 'sweixin', 'weixin_push','sweixin_push')
        ->where('status', null)
        ->get()->toArray();
    
    foreach ($admins1 as $admin) {
        $admins[$admin->id] = $admin;
    }
    
    // 缓存到Redis，有效期1小时（3600秒）
    Redis::setex($cacheKey, 3600, json_encode($admins));
}
 
DB::connection('mysql2')->table('baidu_xinxiliu_project_list')->select('id', 'projectName', 'project_balance', 'conversions_day')
    ->where('project_balance', '>', 0)
      ->chunkById(30, function ($project_list) use ($admins,$response,$startsTime,$endsTime) {
        foreach ($project_list as $project_list_item) {
            $project = DB::connection('mysql2')->table('baidu_xinxiliu_project')
            ->select(
                'id',
                'clientName',
                'sellId',
                'types',
                'youhuashiId',
                'subName'
            )
            ->where('clientName', $project_list_item->projectName)
            ->where('status', 1)
            ->orderBy('id', 'DESC')
            ->get();

        // 批量获取关联数据
        $subuserIds = $project->pluck('subName');
        $subuserData = collect();
        $refreshTokenData = collect();
        
        if ($subuserIds->isNotEmpty()) {
            $subuserData = DB::connection('mysql2')->table('baidu_xinxiliu_subuser')
                ->select('userName', 'userId', 'masterUid', 'id', 'budget', 'userStat')
                ->whereIn('userName', $subuserIds)
                ->get()
                ->keyBy('userName');

            $masterUids = $subuserData->pluck('masterUid');
            
            if ($masterUids->isNotEmpty()) {
                $refreshTokenData = DB::connection('mysql2')->table('baidu_xinxiliu_refreshToken')
                    ->select('userId', 'accessToken')
                    ->whereIn('userId', $masterUids)
                    ->get()
                    ->keyBy('userId');
            }
        }

        // 组装最终数据
        $project = $project->map(function($item) use ($subuserData, $refreshTokenData) {
            $subuser = $subuserData->get($item->subName);
            $refreshToken = null;
            
            if ($subuser) {
                $refreshToken = $refreshTokenData->get($subuser->masterUid);
            }
            
            return [
                'id' => $item->id,
                'clientName' => $item->clientName,
                'sellId' => $item->sellId,
                'types' => $item->types,
                'youhuashiId' => $item->youhuashiId,
                'userId' => $subuser ? $subuser->userId : null,
                'masterUid' => $subuser ? $subuser->masterUid : null,
                'userName' => $subuser ? $subuser->userName : null,
                'sid' => $subuser ? $subuser->id : null,
                'budget' => $subuser ? $subuser->budget : null,
                'userStat' => $subuser ? $subuser->userStat : null,
                'accessToken' => $refreshToken ? $refreshToken->accessToken : null,
            ];
        })->toArray();
            $userIds = array_column($project, 'userId');
            $date = date('Y-m-d');
            
            $reportdata = Db::connection('mysql2')->table('baidu_xinxiliu_reportdata')
                ->select('cost')
                ->where('eventDate', $date)
                ->whereIn('userId', $userIds)
                ->get()->toArray();
            $cost = array_sum(array_column($reportdata, 'cost'));
            $message_error_success = "";
            var_dump($project_list_item->projectName."已消费".$cost."预算".$project_list_item->project_balance);

            if ($cost + 50 >= $project_list_item->project_balance) {   

                    // 优化：先过滤出有效的项目
                     $validProjects = array_filter($project, function($item) {
                                return   !($item['budget'] > 0 && $item['userStat'] == 11);
                            });
                   if (!empty($validProjects)) {
                    foreach($validProjects as $project_item){
                        $updatetime = Redis::get('project_updatetime_' . $date . $project_item['id']);
                        if(!$updatetime){   
                                                       
                             $campaignFeed = $response->getCampaignFeedCrontab([], $project_item, $project_item['accessToken'], null, $startsTime, $endsTime);
                             $message_error_success .= $project_item['userName'].$campaignFeed['message']."\n";
                             if($campaignFeed['code']==0){
                               Redis::set('project_updatetime_' . $date . $project_item['id'], 1);
                               Log::channel('datareportyusuan')->info('百度账号[' . $project_item['userName'] . ']成功 - 项目[' . $project_item['clientName'] . ']'.$campaignFeed['message']).'时段'.$startsTime.'-'.$endsTime;
                           } else {
                             Redis::set('project_updatetime_' . $date . $project_item['id'], 1);
                               Log::channel('datareportyusuan')->info('百度账号[' . $project_item['userName'] . ']失败 - 项目[' . $project_item['clientName'] . ']'.$campaignFeed['message']);
   
                           }
                        }
                     }
                }
            }
            if ($cost + 50 >= $project_list_item->project_balance) {
                //是否提醒过
                $is_push = Redis::get('weixin_push_costis_' . $date . $project_list_item->id);
               if (!$is_push) {
                
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
                                             >百度账号<font color=\"comment\">" . $message_error_success . "</font>。
                                              >时段<font color=\"comment\">" . $startsTime.'-'.$endsTime . "</font>。
                                             >时间: " . date("Y-m-d H:i:s") . "。\n";
                    if (isset($admins[$project[0]['sellId']]))
                        $weixin_message .= ">销售: <font color=\"info\">" . $admins[$project[0]['sellId']]->userName . " </font>。\n";

                    if (isset($admins[$project[0]['youhuashiId']])) {
                        $weixin_message .= ">优化师: <font color=\"info\">" . $youhuashiname . "</font>。\n";
                        $weixin_message .=$weixinMentions;
                    }
                    //销售
                     if (isset($admins[$project[0]['sellId']]->sweixin_push) && $admins[$project[0]['sellId']]->sweixin_push != null)
                     push_work_weixin($admins[$project[0]['sellId']]->sweixin_push, $weixin_message);
                     //优化师
                     foreach ($youhuashiIds as $youhuashiId) {
                        if (isset($admins[$youhuashiId]) && isset($admins[$youhuashiId]->weixin_push) && $admins[$youhuashiId]->weixin_push != null) {
                            push_work_weixin($admins[$youhuashiId]->weixin_push, $weixin_message);
                         }
                    }
                    $workweixin = push_work_weixin('4742b357-8c97-4f7c-9702-86c7986cdf9c', $weixin_message);
                    var_dump($workweixin);
                    //只提醒一次
                    if (is_array($workweixin) && $workweixin['errcode'] == 0)
                        Redis::set('weixin_push_costis_' . $date . $project_list_item->id, 1);
               }
            }
        }

    });
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
Log::channel('datareport')->info('项目日预算 企微提醒数据完毕,ProjectBalance.php 代码运行时间: ' . $executionTime . "秒");