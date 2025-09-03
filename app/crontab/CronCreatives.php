<?php
//创意状态的变化 企微提醒

namespace app\crontab;

use support\Db;
use support\Log;
use support\Redis;
use GuzzleHttp\Client as GuzzleClient;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../support/bootstrap.php';
         
 $admins = [];
 
// 记录开始时间
$startTime = microtime(true);
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


// 记录开始时间
 $url = 'https://api.baidu.com/json/feed/v1/CampaignFeedService/getCampaignFeed';

 $GuzzleClient =new GuzzleClient();

Db::connection('mysql2')->table('baidu_xinxiliu_project')->select('id', 'subName', 'status','youhuashiId','clientName','sellId')
    ->where('status',1)
    // ->where('id',2334)
      ->chunkById(20, function ($project_list)use($url,$GuzzleClient,$admins) {
        foreach ($project_list as $project_list_item) {
            $project = Db::connection('mysql2')->table('baidu_xinxiliu_subuser')
            ->leftJoin('baidu_xinxiliu_refreshToken', 'baidu_xinxiliu_subuser.masterUid', '=', 'baidu_xinxiliu_refreshToken.userId')
            ->select(
                 'baidu_xinxiliu_subuser.userName',
                'baidu_xinxiliu_refreshToken.accessToken'
            )
            ->where('baidu_xinxiliu_subuser.userName', trim($project_list_item->subName))
            ->orderBy('baidu_xinxiliu_subuser.id', 'DESC')
            ->first();
            //如果$project为空，则跳过    accessToken是否为空
            if(empty($project) || empty($project->accessToken)){
                // Log::channel('plancreative')->info('百度账号[' . $project_list_item->subName . ']不存在');
                continue;
            }
            $result = getCampaignFeed($url,$GuzzleClient,$project);
            
            if($result['code'] == 200 ){
                $campaignFeedIds = $result['campaignFeedIds'];
                //根据计划id查询创意信息
                $creativeFeed = getCreativeFeedByCampaignIds($campaignFeedIds,$project,$GuzzleClient, $project_list_item,$admins);
             }else{
                $campaignFeedIds = [];
             }
    }
    });
    /**
     * 查询计划id
     * @param array $campaignIds
     * @return array
     */
    
   function getCampaignFeed($url,$GuzzleClient,$project){
              //header
              $user_payload = array(
                "header" => array(
                    "userName" => $project->userName,
                    "accessToken" => $project->accessToken,
                    "action" => "API-PYTHON"
                ),
            );
            //body
            $user_payload['body'] = [
                'campaignFeedFields' => ['campaignFeedId'],
            ];
            try {
    
                // 发送异步请求并等待响应
                $response = $GuzzleClient->postAsync($url, ['json' => $user_payload])->wait();
                $result = json_decode($response->getBody(), true);
                 // 验证响应是否成功
                if (isApiResponseSuccess($result)) {
                    // 提取符合条件的campaignFeedId
                    $campaignFeedIds = extractCampaignFeedIds($result);
     
                    if(count($campaignFeedIds) > 0) {
                        // Log::channel('plancreative')->info('获取计划ID成功');
                        return [
                            'code'=>200,
                            'success' => true,
                            'message' => '获取计划ID成功',
                            'campaignFeedIds' =>$campaignFeedIds
                        ];    
                    } else {
                        // Log::channel('plancreative')->info('该账户下没有新建计划');
                        return [
                            'code'=>300,
                            'success' => false,
                            'message' => '该账户下没有新建计划',
                            'campaignFeedIds' =>[] 
                        ];    
                    }
                } else {
                    // Log::channel('plancreative')->info('接口请求失败');

                    return [
                        'code'=>300,
                        'success' => false,
                        'message' => '接口请求失败',
                        'campaignFeedIds' =>[] 
                    ];    
                }
            } catch (\Exception $e) {
                // Log::channel('plancreative')->info('500接口请求失败');

                return [
                    'code'=>500,
                    'success' => false,
                    'message' => '500接口请求失败',
                    'campaignFeedIds' =>[] 
                ];                
            }
 }

    /**
     * 根据计划ID数组查询创意信息
     * @param array $campaignIds
     * @return array
     */
      function getCreativeFeedByCampaignIds($campaignIds,$project,$GuzzleClient,$project_list_item,$admins)
    {
         $url = 'https://api.baidu.com/json/feed/v1/CreativeFeedService/getCreativeFeed';

        if (empty($campaignIds)) {
            // Log::channel('plancreative')->info('计划ID数组为空');
            return [
                'success' => false,
                'message' => '计划ID数组为空',
                'data' => [],
                'total' => 0
            ];
 
        }

         
        // 构建请求头
        $header = [
            "userName" => $project->userName,
            "accessToken" => $project->accessToken,
            "action" => "API-PYTHON"
        ];

        // 构建请求体 - 使用传入的计划ID数组
        $body = [
            'creativeFeedFields' => [
                'creativeFeedId',
                'creativeFeedName', 
                'status',
                'pause'
            ],
            'ids' => $campaignIds,
            //1 计划  2 单元 3 创意
            'idType' => 1
        ];

        // 完整的请求数据
        $payload = [
            "header" => $header,
            "body" => $body
        ];

        try {
             // 发送POST请求
            $response = $GuzzleClient->post($url, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            $result = json_decode($response->getBody(), true);
           

            // 验证响应是否成功
            if ( isApiResponseSuccess($result)) {

                $creativeData = extractCreativeData($result);
                //  处理创意数据状态变化
                if (!empty($creativeData)) {
                     processCreativeStatusChanges($creativeData, $project_list_item,$admins);
                 }
                
            } else {
                // Log::channel('plancreative')->info('API请求失败getCreativeFeedByCampaignIds');

            }

        } catch (\Exception $e) {
            // Log::channel('plancreative')->info('Internal error getCreativeFeedByCampaignIds');

        }
    }
/**
     * 提取创意数据
     * @param array $result
     * @return array
     */
      function extractCreativeData($result)
    {
        $creativeData = [];
        
        if (isset($result['body']['data']) && is_array($result['body']['data'])) {
            foreach ($result['body']['data'] as $item) {
                $creativeData[] = [
                    'creativeFeedId' => $item['creativeFeedId'] ?? null,
                    'creativeFeedName' => $item['creativeFeedName'] ?? null,
                    'status' => $item['status'] ?? null,
                    'pause' => $item['pause'] ?? null
                ];
            }
        }
        
        return $creativeData;
    }

/**
 * 处理创意状态变化
 * @param array $creativeData 创意数据数组
 * @param int $projectId 项目ID
 */
function processCreativeStatusChanges($creativeData,$project_list_item,$admins)
{
    // 状态映射
    $statusMap = [
        0 => '有效',//绿色
        1 => '暂停推广',//黄色
        2 => '审核中',//蓝色
        3 => '审核未通过',//红色
        5 => '部分有效',//黄色
        6 => '未审核'//蓝色
    ];
    
    // 状态颜色映射 - 企微支持的颜色值
    $statusColorMap = [
        0 => 'info',      // 有效 - 绿色
        1 => 'warning',   // 暂停推广 - 橙色/黄色
        2 => 'comment',   // 审核中 - 灰色 
        3 => 'warning',     // 审核未通过 - 红色
        5 => 'warning',   // 部分有效 - 橙色/黄色
        6 => 'comment'    // 未审核 - 灰色
    ];
    
    // 用于存储状态变化的创意分组
    $statusChangeGroups = [];
    
    foreach ($creativeData as $creative) {
        $creativeId = $creative['creativeFeedId'];
        $currentStatus = $creative['status'];
        $creativeName = $creative['creativeFeedName'];
        
        // 构建Redis key: 项目id.创意id (添加creative_前缀避免历史数据干扰)
        $redisKey = "creative_".$project_list_item->id."_".$creativeId;
        

        try {
            // 从Redis获取之前存储的状态
            $previousStatus = Redis::get($redisKey);
             if ($previousStatus !== null) {
                // 如果之前有状态记录，比较状态是否一致
                $previousStatus = (int)$previousStatus;
                // $currentStatus = 5;
                if ($previousStatus !== $currentStatus) {
                    // 将状态变化按当前状态分组
                    $statusKey = $currentStatus;
                    if (!isset($statusChangeGroups[$statusKey])) {
                        $statusChangeGroups[$statusKey] = [
                            'count' => 0,
                            'creatives' => [],
                            'previousStatus' => $previousStatus,
                            'currentStatus' => $currentStatus
                        ];
                    }
                    $statusChangeGroups[$statusKey]['count']++;
                    $statusChangeGroups[$statusKey]['creatives'][] = [
                        'id' => $creativeId,
                        'name' => $creativeName
                    ];
                    
                    // 更新Redis中的状态
                    Redis::setex($redisKey, 2592000, $currentStatus); 
          
                }else{
                    // Log::channel('plancreative')->info('百度账号[' . $project_list_item->subName . ']状态没变化');
                }
            }else{
                Redis::setex($redisKey, 2592000, $currentStatus); 
            }
            

            
        } catch (\Exception $e) {
            // Log::channel('plancreative')->info('Internal error processCreativeStatusChanges');
        }
    }
    
    // 如果有状态变化，整合所有状态变化到一条消息中
    if (!empty($statusChangeGroups)) {
        sendWecomNotificationConsolidated($project_list_item, $statusChangeGroups, $statusMap, $statusColorMap, $admins);
    }
}

/**
 * 发送整合企微通知（所有状态变化整合到一条消息）
 * @param object $project_list_item 项目信息对象
 * @param array $statusChangeGroups 所有状态变化分组数据
 * @param array $statusMap 状态映射
 * @param array $statusColorMap 状态颜色映射
 * @param array $admins 管理员数组
 */
function sendWecomNotificationConsolidated($project_list_item, $statusChangeGroups, $statusMap, $statusColorMap, $admins = [])
{
     try {
        $weixin_message = "项目：<font color=\"info\">" . $project_list_item->clientName."</font><font color=\"info\">审核状态发生变化</font> 
                                             >百度账号:<font color=\"comment\">" . $project_list_item->subName . "</font>。\n";
        
        // 整合所有状态变化信息
        foreach ($statusChangeGroups as $statusKey => $groupData) {
            $statusColor = isset($statusColorMap[$groupData['currentStatus']]) ? $statusColorMap[$groupData['currentStatus']] : 'info';
            $weixin_message .= ">状态变化：从<font color=\"" . $statusColor . "\">" . $statusMap[$groupData['previousStatus']] . "</font>变为<font color=\"" . $statusColor . "\">" . $statusMap[$groupData['currentStatus']] . "</font>。
                                             >影响创意数量：<font color=\"warning\">" . $groupData['count'] . "</font>个。\n";
        }
        
        $weixin_message .= ">时间: " . date("Y-m-d H:i:s") . "。\n";
        // 销售
        if (isset($admins[$project_list_item->sellId])) {
            $admin = $admins[$project_list_item->sellId];
            $weixin_message .= ">销售: <font color=\"info\">" . $admin->userName . "</font>。\n";
            if (isset($admin->sweixin) && $admin->sweixin) {
                $weixin_message .= "<@" . $admin->sweixin . ">";
            }
        
        }                
        if (isset($admins[$project_list_item->youhuashiId])) {
            $admin = $admins[$project_list_item->youhuashiId];
            $weixin_message .= ">优化师: <font color=\"info\">" . $admin->userName . "</font>。\n";
            if (isset($admin->weixin) && $admin->weixin) {
                $weixin_message .= "<@" . $admin->weixin . ">";
            }
        }
        // 优化师
        if (isset($admin->weixin_push) && $admin->weixin_push != null)
        push_work_weixin($admin->weixin_push, $weixin_message);

        //给销售发企微
        if (isset($admins[$project_list_item->sellId]) && isset($admins[$project_list_item->sellId]->sweixin_push) && $admins[$project_list_item->sellId]->sweixin_push != null) {
            push_work_weixin($admins[$project_list_item->sellId]->sweixin_push, $weixin_message);
        }
        $workweixin = push_work_weixin('a8d89e6d-40bc-4e04-93ff-761cbba41c06', $weixin_message);
        // 发送成功记录日志
        $logMessage = $project_list_item->clientName . '_';
        foreach ($statusChangeGroups as $groupData) {
            $logMessage .= $statusMap[$groupData['previousStatus']] . '_' . $statusMap[$groupData['currentStatus']] . '_' . $groupData['count'] . '个;';
        }
        Log::channel('plancreative')->info($logMessage);
        
        // 调试输出
        var_dump($weixin_message);
        
    } catch (\Exception $e) {
        // Log::channel('plancreative')->info('Internal error sendWecomNotificationConsolidated');
    }
}
 
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
Log::channel('plancreative')->info('创意状态变化 企微提醒数据完毕,CronCreatives.php 代码运行时间: ' . $executionTime . "秒");