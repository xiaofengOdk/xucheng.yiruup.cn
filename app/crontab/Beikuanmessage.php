<?php
//备款项目余额监控 企微提醒

namespace app\crontab;

use support\Db;
use support\Log;
use support\Redis;
use GuzzleHttp\Client as GuzzleClient;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../support/bootstrap.php';

// 记录开始时间
$startTime = microtime(true);

// 记录开始时间
$url = 'https://api.baidu.com/json/feed/v1/CampaignFeedService/getCampaignFeed';

$GuzzleClient = new GuzzleClient();

// 查询管理员信息（id为3的管理员）
$admin = Db::connection('mysql2')->table('wa_admins')
    ->select('id', 'userName', 'weixin', 'weixin_push')
    ->where('id', 3)
    ->first();

// 查询备款项目（使用关联查询获取balance1）
Db::connection('mysql2')->table('baidu_xinxiliu_project as p')
    ->leftJoin('baidu_xinxiliu_subuser as s', 'p.subName', '=', 's.userName')
    ->select('p.id', 'p.subName', 'p.status', 'p.youhuashiId', 'p.clientName', 's.balance1')
    ->where('p.clientName', "备款") // 只查询备款
     ->chunkById(50, function ($project_list) use ($url, $GuzzleClient, $admin) {
        foreach ($project_list as $project_list_item) {
            // 构建Redis键名
            $redisKey = $project_list_item->id . "_bkt_" . $project_list_item->subName;
            
            // 获取当前余额
            $currentBalance = $project_list_item->balance1;
            
            // 获取Redis中存储的旧余额
            $oldBalance = Redis::get($redisKey);
      
            
            // 如果旧余额存在且与新余额不同，则处理
            if ($oldBalance !== null) {
                if ($oldBalance != $currentBalance) {
                    // 只有当余额小于一万时才发送通知
                    if ($currentBalance < 10000) {
                        // 构建状态变化数据
                        $statusChangeGroups = [
                            'balance' => [
                                'previousStatus' => 'old_balance',
                                'currentStatus' => 'new_balance',
                                'count' => 1,
                                'oldValue' => $oldBalance,
                                'newValue' => $currentBalance
                            ]
                        ];
                        
                        // 状态映射
                        $statusMap = [
                            'old_balance' => '旧余额：' . $oldBalance,
                            'new_balance' => '新余额：' . $currentBalance
                        ];
                        
                        // 状态颜色映射
                        $statusColorMap = [
                            'old_balance' => 'info',
                            'new_balance' => $currentBalance > $oldBalance ? 'green' : 'warning'
                        ];
                        
                        // 发送企微通知 企微通知之后 更新redis
                        sendWecomNotificationConsolidated($project_list_item, $statusChangeGroups, $statusMap, $statusColorMap, $admin, $redisKey, $currentBalance);

                        // 记录日志
                        Log::channel('beikuantixing')->info("备款项目余额变化：{$project_list_item->subName}，从{$oldBalance}变为{$currentBalance}，余额小于一万，已发送通知");
                    } else {
                        // 余额大于等于一万，只更新redis，不发送通知
                        Redis::set($redisKey, $currentBalance);
                        Log::channel('beikuantixing')->info("备款项目余额变化：{$project_list_item->subName}，从{$oldBalance}变为{$currentBalance}，余额大于等于一万，仅更新redis");
                    }
                } 
            } else {
                // 首次记录，设置redis
                Redis::set($redisKey, $currentBalance);
            }
        }
    });

/**
 * 发送整合企微通知（所有状态变化整合到一条消息）
 * @param object $project_list_item 项目信息对象
 * @param array $statusChangeGroups 所有状态变化分组数据
 * @param array $statusMap 状态映射
 * @param array $statusColorMap 状态颜色映射
 * @param object $admin 管理员对象
 */
function sendWecomNotificationConsolidated($project_list_item, $statusChangeGroups, $statusMap, $statusColorMap, $admin,$redisKey,$currentBalance)
{
    try {
        $weixin_message = "项目：<font color=\"info\">" . $project_list_item->clientName . "</font><font color=\"info\">余额发生变化</font> 
                                             >百度账号:<font color=\"comment\">" . $project_list_item->subName . "</font>。\n";

        // 整合所有状态变化信息
        foreach ($statusChangeGroups as $statusKey => $groupData) {
            $statusColor = isset($statusColorMap[$groupData['currentStatus']]) ? $statusColorMap[$groupData['currentStatus']] : 'info';
            $weixin_message .= ">余额变化：从<font color=\"info\">" . $statusMap[$groupData['previousStatus']] . "</font>变为<font color=\"" . $statusColor . "\">" . $statusMap[$groupData['currentStatus']] . "</font>。\n";
            
            // 计算变化金额
            if (isset($groupData['oldValue']) && isset($groupData['newValue'])) {
                $diff = $groupData['newValue'] - $groupData['oldValue'];
                $changeDirection = $diff > 0 ? "增加" : "减少";
                $absChange = abs($diff);
                
                $weixin_message .= ">变化金额：<font color=\"warning\">" . $changeDirection . " " . $absChange . "</font>。\n";
            }
        }

        $weixin_message .= ">时间: " . date("Y-m-d H:i:s") . "。\n";

        // 添加管理员信息
        if ($admin) {
            $weixin_message .= ">管理员: <font color=\"info\">" . $admin->userName . "</font>。\n";
            if (isset($admin->weixin) && $admin->weixin) {
                $weixin_message .= "<@" . $admin->weixin . ">";
            }
        }

        // 发送企微通知
        if (isset($admin->weixin_push) && $admin->weixin_push != null) {
            push_work_weixin($admin->weixin_push, $weixin_message);
        }  
        push_work_weixin('4742b357-8c97-4f7c-9702-86c7986cdf9c', $weixin_message);
        Redis::set($redisKey, $currentBalance);
        // 发送成功记录日志
        $logMessage = $project_list_item->clientName . '_' . $project_list_item->subName . '_';
        foreach ($statusChangeGroups as $groupData) {
            $logMessage .= $statusMap[$groupData['previousStatus']] . '_' . $statusMap[$groupData['currentStatus']] . '_' . $groupData['count'] . '个;';
        }
        Log::channel('beikuantixing')->info($logMessage);
 
    } catch (\Exception $e) {
        Log::channel('beikuantixing')->error('发送企微通知失败: ' . $e->getMessage());
    }
}

$endTime = microtime(true);
$executionTime = $endTime - $startTime;
Log::channel('beikuantixing')->info('备款项目余额监控企微提醒数据完毕,Beikuanmessage.php 代码运行时间: ' . $executionTime . "秒");
