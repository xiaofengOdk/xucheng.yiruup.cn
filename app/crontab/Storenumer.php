<?php
//单元到店量 企微提醒

namespace app\crontab;

use GuzzleHttp\Client as GuzzleClient;
use support\Db;
use support\Log;
use support\Redis;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../support/bootstrap.php';

// 记录开始时间
$startTime = microtime(true);

$admins = [];

$cacheKey = 'wa_admins_cache';
$cachedAdmins = Redis::get($cacheKey);

if ($cachedAdmins) {
    // 将Redis缓存的数据转换为对象格式，保持与数据库查询结果一致
    $adminsArray = json_decode($cachedAdmins, true);
    foreach ($adminsArray as $id => $adminData) {
        $admins[$id] = (object)$adminData;  // 转换为对象
    }
} else {
    $admins1 = Db::connection('mysql2')->table('wa_admins')
        ->select('id', 'userName', 'weixin', 'sweixin', 'weixin_push', 'sweixin_push')
        ->where('status', null)
        ->get()->toArray();

    foreach ($admins1 as $admin) {
        $admins[$admin->id] = $admin;
    }

    // 缓存到Redis，有效期1小时（3600秒）
    Redis::setex($cacheKey, 3600, json_encode($admins));
}

$GuzzleClient = new GuzzleClient();

// 查询原生推广单元的url
$getCampaignFeedUrl = 'https://api.baidu.com/json/feed/v1/CampaignFeedService/getCampaignFeed';
$getAdgroupFeedUrl = 'https://api.baidu.com/json/feed/v1/AdgroupFeedService/getAdgroupFeed';
$updateAdgroupFeedUrl = 'https://api.baidu.com/json/feed/v1/AdgroupFeedService/updateAdgroupFeed';

DB::connection('mysql2')->table('baidu_xinxiliu_project')
    ->select('id', 'clientName', 'sellId', 'types', 'youhuashiId', 'subName', 'status', 'storenumber')
    ->where('status', 1)
    ->where('storenumber', '>', 0)
    ->chunkById(20, function ($project_list) use ($getAdgroupFeedUrl, $updateAdgroupFeedUrl, $GuzzleClient, $admins, $getCampaignFeedUrl) {
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
            if (empty($project) || empty($project->accessToken)) {
                // Log::channel('storenumer')->info('百度账号[' . $project_list_item->subName . ']不存在或accessToken为空');
                continue;
            }


            // 根据types判断处理方式 types 1 表单 2 加粉
            if ($project_list_item->types == 1) {
                // 表单处理逻辑
                $formProcessingResult = processFormSubmission($project_list_item, $project);
                if ($formProcessingResult) {
                    Log::channel('storenumer')->info('项目[' . $project_list_item->clientName . ']百度账号[' . $project_list_item->subName . ']表单处理完成');
                    continue; // 跳过后续的到店量处理
                }
            } elseif ($project_list_item->types == 2) {
                // 查询该百度账号下所有的计划ID   
                $result = getCampaignFeed($getCampaignFeedUrl, $GuzzleClient, $project);

                if ($result['code'] == 200 && !empty($result['campaignFeedIds'])) {
                    $campaignFeedIds = $result['campaignFeedIds'];
                    //根据计划id查询单元信息
                    $adgroupFeedResult = getAdgroupFeed($getAdgroupFeedUrl, $campaignFeedIds, $project, $GuzzleClient, $project_list_item, $admins, $updateAdgroupFeedUrl);
                    if ($adgroupFeedResult) {
                        Log::channel('storenumer')->info('项目[' . $project_list_item->clientName . ']百度账号[' . $project_list_item->subName . ']加粉处理完成');
                    }
                } else {
                    Log::channel('storenumer')->info('项目[' . $project_list_item->clientName . ']百度账号[' . $project_list_item->subName . ']获取计划ID失败');
                }
            }


        }
    });
if (!function_exists('getCampaignFeed')) {
    /**
     * 查询百度账号下所有计划ID
     * @param string $url API地址
     * @param GuzzleClient $GuzzleClient HTTP客户端
     * @param object $project 项目信息
     * @return array
     */
    function getCampaignFeed($url, $GuzzleClient, $project)
    {
        // 构建请求头
        $header = [
            "userName" => $project->userName,
            "accessToken" => $project->accessToken,
            "action" => "API-PYTHON"
        ];

        // 构建请求体 - 查询所有计划
        $body = [
            'campaignFeedFields' => ['campaignFeedId']
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
            if (isApiResponseSuccess($result)) {
                $campaignFeedIds = [];
                if (isset($result['body']['data']) && is_array($result['body']['data'])) {
                    foreach ($result['body']['data'] as $item) {
                        if (isset($item['campaignFeedId'])) {
                            $campaignFeedIds[] = $item['campaignFeedId'];
                        }
                    }
                }

                return [
                    'code' => 200,
                    'success' => true,
                    'message' => '获取计划ID成功',
                    'campaignFeedIds' => $campaignFeedIds
                ];
            } else {
                Log::channel('storenumer')->error('获取计划ID失败: ' . json_encode($result));
                return [
                    'code' => 300,
                    'success' => false,
                    'message' => '获取计划ID失败',
                    'campaignFeedIds' => []
                ];
            }
        } catch (\Exception $e) {
            Log::channel('storenumer')->error('获取计划ID异常: ' . $e->getMessage());
            return [
                'code' => 500,
                'success' => false,
                'message' => '获取计划ID异常: ' . $e->getMessage(),
                'campaignFeedIds' => []
            ];
        }
    }
}
if (!function_exists('getAdgroupFeed')) {
    /**
     * 根据计划ID数组查询推广单元信息
     * @param string $url API地址
     * @param array $campaignIds 计划ID数组
     * @param object $project 项目信息
     * @param GuzzleClient $GuzzleClient HTTP客户端
     * @param object $project_list_item 项目列表项
     * @param array $admins 管理员数组
     * @param string $updateUrl 更新API地址
     * @return bool
     */
    function getAdgroupFeed($url, $campaignIds, $project, $GuzzleClient, $project_list_item, $admins, $updateUrl)
    {
        if (empty($campaignIds)) {
            Log::channel('storenumer')->info('计划ID数组为空');
            return false;
        }

        // 构建请求头
        $header = [
            "userName" => $project->userName,
            "accessToken" => $project->accessToken,
            "action" => "API-PYTHON"
        ];

        // 构建请求体 - 查询推广单元
        $body = [
            'adgroupFeedFields' => [
                'adgroupFeedId',
                'adgroupFeedName',
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
            if (isApiResponseSuccess($result)) {
                $adgroupData = extractAdgroupData($result);
                // 处理推广单元数据

                if (!empty($adgroupData)) {
                    processAdgroupStatusChanges($adgroupData, $project_list_item, $project, $admins, $updateUrl);
                }
                return true;
            } else {
                Log::channel('storenumer')->error('查询推广单元失败: ' . json_encode($result));
                return false;
            }
        } catch (\Exception $e) {
            Log::channel('storenumer')->error('查询推广单元异常: ' . $e->getMessage());
            return false;
        }
    }
}
if (!function_exists('extractAdgroupData')) {
    /**
     * 提取推广单元数据
     * @param array $result API响应结果
     * @return array
     */
    function extractAdgroupData($result)
    {
        $adgroupData = [];
        if (isset($result['body']['data']) && is_array($result['body']['data'])) {
            foreach ($result['body']['data'] as $item) {
                //如果$item['status'] != 0 代表该单元处于暂停状态 则不需要判断到店量
                if (isset($item['status']) && $item['status'] != 0) {
                    continue;
                }
                if (isset($item['adgroupFeedId'])) {
                    $adgroupData[] = [
                        'adgroupFeedId' => $item['adgroupFeedId'],
                        'adgroupFeedName' => $item['adgroupFeedName'] ?? '',
                        'status' => $item['status'] ?? 0,
                        'pause' => $item['pause'] ?? false
                    ];
                }
            }
        }
        return $adgroupData;
    }
}
if (!function_exists('processAdgroupStatusChanges')) {
    /**
     * 处理推广单元状态变化
     * @param array $adgroupData 推广单元数据
     * @param object $project_list_item 项目列表项
     * @param object $project 项目信息
     * @param array $admins 管理员数组
     * @param string $updateUrl 更新API地址
     */
    function processAdgroupStatusChanges($adgroupData, $project_list_item, $project, $admins, $updateUrl)
    {

        $date = date('Y-m-d');

        // 查询该单元的当天到店量
        $storeNumber = getStoreNumberFromBaiduReport($project, $date, $project_list_item, $admins, $updateUrl, $adgroupData);

    }
}
if (!function_exists('getStoreNumberFromBaiduReport')) {
    /**
     * 从百度数据报告接口获取到店量
     * @param object $project 项目信息
     * @param string $date 日期
     * @param object $project_list_item 项目列表项
     * @param array $admins 管理员数组
     * @param string $updateAdgroupFeedUrl 更新推广单元API地址
     * @return int
     */
    function getStoreNumberFromBaiduReport($project, $date, $project_list_item, $admins, $updateAdgroupFeedUrl, $adgroupData)
    {
        try {
            global $GuzzleClient;

            // 百度数据报告接口
            $reportUrl = 'https://api.baidu.com/json/sms/service/OpenApiReportService/getReportData';

            // 构建请求头
            $header = [
                "userName" => $project->userName,
                "accessToken" => $project->accessToken,
                "action" => "API-PYTHON"
            ];

            // 构建请求体 - 信息流数据报告，尝试获取单元级别数据
            $body = [
                "reportType" => 2330652
                , // 信息流数据报告
                "startDate" => $date,
                "endDate" => $date,
                "timeUnit" => "DAY",
                "columns" => [
                    "date",
                    "adGroupNameStatus",
                    "userId",
                    "adGroupId",
                    "adGroupStatus",
                    "visitStore" // 到店量字段
                ],
                "sorts" => [],
                "filters" => [

                ],
                "startRow" => 0,
                "rowCount" => 1000,
                "needSum" => false
            ];

            $payload = [
                "header" => $header,
                "body" => $body
            ];

            // 发送POST请求
            $response = $GuzzleClient->post($reportUrl, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
            ]);

            $result = json_decode($response->getBody(), true);


            if (isApiResponseSuccess($result) && isset($result['body']['data'])) {
                foreach ($result['body']['data'] as $dataItem) {
                    if (isset($dataItem['rows']) && is_array($dataItem['rows'])) {
                        foreach ($dataItem['rows'] as $row) {
                            // 获取到每一个单元所对应的到店量之后，先判断是否包含[已删除]
                            if (isset($row['adGroupNameStatus']) && strpos($row['adGroupNameStatus'], '[已删除]') !== false) {
                                // Log::channel('storenumer')->info('跳过已删除的推广单元: ' . ($row['adGroupId'] ?? 'N/A'));
                                continue;
                            }
                            //默认空
                            $tongzhi_message = "";

                            if (!in_array($row['adGroupId'], array_column($adgroupData, 'adgroupFeedId'))) {
                                $tongzhi_message = "单元[" . ($row['adGroupId'] ?? 'N/A') . "]已经停止过";
                                Log::channel('storenumer')->info('百度账号[' . $project->userName . ']单元[' . ($row['adGroupId'] ?? 'N/A') . ']已是停止状态，跳过');
                                continue;
                            }

                            $logData = [
                                'date' => $row['date'] ?? 'N/A',
                                'adGroupNameStatus' => $row['adGroupNameStatus'] ?? 'N/A',
                                'adGroupId' => $row['adGroupId'] ?? 'N/A',
                                'visitStore' => $row['visitStore'] ?? 0
                            ];

                            Log::channel('storenumer')->info('百度账号[' . $project->userName . ']到店量数据: ' . json_encode($logData, JSON_UNESCAPED_UNICODE));
                            // 检查Redis缓存，防止重复处理
                            $processKey = 'proc_' . $project_list_item->id . '_' . ($row['adGroupId'] ?? 'unknown') . '_' . date('Y-m-d');
                            if (Redis::exists($processKey)) {
                                //  Log::channel('storenumer')->info('单元[' . ($row['adGroupId'] ?? 'N/A') . ']已经处理过，跳过');
                                continue;
                            }
                            // 将该单元所对应的到店量与数据库中设置的到店量进行对比
                            if (isset($row['visitStore']) && is_numeric($row['visitStore'])) {
                                $actualStoreNumber = intval($row['visitStore']);
                                $expectedStoreNumber = $project_list_item->storenumber;

                                Log::channel('storenumer')->info('百度账号[' . $project->userName . ']单元[' . ($row['adGroupId'] ?? 'N/A') . ']到店量对比: 实际=' . $actualStoreNumber . ', 期望=' . $expectedStoreNumber);

                                // 如果大于等于则更新该单元的暂停状态为true

                                if ($actualStoreNumber >= $expectedStoreNumber) {

                                    // 更新推广单元状态为暂停
                                    $updateResult = updateAdgroupPauseStatus($updateAdgroupFeedUrl, $row['adGroupId'], $project, true);
                                    if ($updateResult) {
                                        // 设置Redis缓存，防止重复处理
                                        Redis::setex($processKey, 86400, 1); // 24小时过期


                                        // 发送企微通知
                                        $adgroupInfo = [
                                            'adgroupFeedId' => $row['adGroupId'],
                                            'adgroupFeedName' => $row['adGroupNameStatus'] ?? '未知单元'
                                        ];

                                        // 检查企微通知是否已发送过
                                        $notifyKey = 'notify_' . $project_list_item->id . '_' . ($row['adGroupId'] ?? 'unknown') . '_' . date('Y-m-d');
                                        if (!Redis::exists($notifyKey)) {
                                            sendStoreNumberNotification($project_list_item, $adgroupInfo, $actualStoreNumber, $expectedStoreNumber, $project, $admins, $tongzhi_message);

                                            // 设置企微通知缓存，防止重复发送
                                            Redis::setex($notifyKey, 86400, 1); // 24小时过期

                                            Log::channel('storenumer')->info('百度账号[' . $project->userName . ']企微通知发送成功，单元: ' . ($row['adGroupId'] ?? 'N/A'));
                                        } else {
                                            Log::channel('storenumer')->info('百度账号[' . $project->userName . ']企微通知已发送过，跳过，单元: ' . ($row['adGroupId'] ?? 'N/A'));
                                        }
                                    } else {
                                        Log::channel('storenumer')->error('百度账号[' . $project->userName . ']单元[' . ($row['adGroupId'] ?? 'N/A') . ']状态更新失败');
                                    }
                                } else {
                                    Log::channel('storenumer')->info('百度账号[' . $project->userName . ']单元[' . ($row['adGroupId'] ?? 'N/A') . ']到店量未达标: ' . $actualStoreNumber . ' < ' . $expectedStoreNumber);
                                }
                            }
                        }
                    }
                }
            }

            return 0;

        } catch (\Exception $e) {
            Log::channel('storenumer')->error(' 从百度数据报告接口获取到店量异常: ' . $e->getMessage());
            return 0;
        }
    }
}
if (!function_exists('updateAdgroupPauseStatus')) {
    /**
     * 更新推广单元的暂停状态
     * @param string $url 更新API地址
     * @param string $adgroupFeedId 推广单元ID
     * @param object $project 项目信息
     * @param bool $pause 是否暂停
     * @return bool
     */
    function updateAdgroupPauseStatus($url, $adgroupFeedId, $project, $pause)
    {
        // 构建请求头
        $header = [
            "userName" => $project->userName,
            "accessToken" => $project->accessToken,
            "action" => "API-PYTHON"
        ];

        // 构建请求体 - 更新推广单元状态
        $body = [
            'adgroupFeedTypes' => [
                [
                    'adgroupFeedId' => $adgroupFeedId,
                    'pause' => $pause // true - 暂停, false - 启用
                ]
            ]
        ];

        // 完整的请求数据
        $payload = [
            "header" => $header,
            "body" => $body
        ];

        try {
            $GuzzleClient = new GuzzleClient();
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
            if (isApiResponseSuccess($result)) {
                return true;
            } else {
                Log::channel('storenumer')->error('更新推广单元状态失败: ' . json_encode($result));
                return false;
            }
        } catch (\Exception $e) {
            Log::channel('storenumer')->error('更新推广单元状态异常: ' . $e->getMessage());
            return false;
        }
    }
}
if (!function_exists('sendStoreNumberNotification')) {
    /**
     * 发送到店量企微通知
     * @param object $project_list_item 项目列表项
     * @param array $adgroup 推广单元信息
     * @param int $actualStoreNumber 实际到店量
     * @param int $expectedStoreNumber 期望到店量
     * @param object $project 项目信息
     * @param array $admins 管理员数组
     */
    function sendStoreNumberNotification($project_list_item, $adgroup, $actualStoreNumber, $expectedStoreNumber, $project, $admins, $tongzhi_message)
    {
        try {
            if ($tongzhi_message == "") {
                $tongzhi_message = "暂停推广设置成功";
            } else {
                $tongzhi_message = "通知信息：" . $tongzhi_message . "\n";
            }
            $weixin_message = "项目：<font color=\"info\">" . $project_list_item->clientName . "</font>\n";
            $weixin_message .= ">百度账号：" . $project->userName . "。\n";
            $weixin_message .= ">推广单元：" . $adgroup['adgroupFeedName'] . "\n";
            $weixin_message .= ">到店量数据库设置的值：" . $expectedStoreNumber . "\n";
            $weixin_message .= ">实际单元对应的值：" . $actualStoreNumber . "\n";
            $weixin_message .= ">通知信息：" . $tongzhi_message . "\n";
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

            // // 发送到公共群
            push_work_weixin('4742b357-8c97-4f7c-9702-86c7986cdf9c', $weixin_message);
            Log::channel('storenumer')->info('百度账号[' . $project->userName . ']到店量企微通知发送成功: ' . $project_list_item->clientName);

        } catch (\Exception $e) {
            Log::channel('storenumer')->error('发送到店量企微通知异常: ' . $e->getMessage());
        }
    }
}
if (!function_exists('isApiResponseSuccess')) {
    /**
     * 验证API响应是否成功
     * @param array $result API响应结果
     * @return bool
     */
    function isApiResponseSuccess($result)
    {
        return isset($result['header']['status']) && $result['header']['status'] == 0;
    }
}
$endTime = microtime(true);
$executionTime = $endTime - $startTime;

// 记录执行统计信息
Log::channel('storenumer')->info('单元到店量企微提醒数据完毕, Storenumer.php 代码运行时间: ' . $executionTime . "秒");
if (!function_exists('processFormSubmission')) {
    /**
     * 处理表单提交逻辑
     * @param object $project_list_item 项目列表项
     * @param object $project 项目信息
     * @return bool
     */
    function processFormSubmission($project_list_item, $project)
    {
        try {
            global $admins, $updateAdgroupFeedUrl;
            $date = date('Y-m-d');
            $ucName = $project_list_item->subName;
            // 查询该账号下当天的表单提交数据
            $formData = getFormDataByDate($ucName, $date);

            if (empty($formData)) {
                Log::channel('storenumer')->info('百度账号[' . $ucName . ']当天无表单数据');
                return true;
            }

            // 按单元ID分组统计表单数量
            $unitFormCounts = getUnitFormCounts($formData);

            // 处理每个单元的表单数量
            foreach ($unitFormCounts as $unitId => $formCount) {

                // 检查Redis是否已经处理过
                $processKey = 'proc_' . $project_list_item->id . '_' . $unitId . '_' . $date;

                if (Redis::exists($processKey)) {
                    Log::channel('storenumer')->info('百度账号[' . $ucName . ']单元[' . $unitId . ']已经处理过，跳过');
                    continue;
                }

                // 对比表单数量和storenumber
                if ($formCount >= $project_list_item->storenumber) {
                    // 检查单元是否已经暂停
                    $isPaused = checkUnitPauseStatus($ucName, $unitId, $project);
                    if (!$isPaused) {
                        // 使用updateAdgroupPauseStatus接口暂停单元
                        $pauseResult = updateAdgroupPauseStatus($updateAdgroupFeedUrl, $unitId, $project, true);

                        if ($pauseResult) {
                            // 存储到Redis，标记已处理（只存储简单标记）
                            Redis::setex($processKey, 86400, 1); // 24小时过期

                            // 发送企微通知
                            $adgroupInfo = [
                                'adgroupFeedId' => $unitId,
                                'adgroupFeedName' => '表单单元-' . $unitId
                            ];

                            // 检查企微通知是否已发送过
                            $notifyKey = 'notify_' . $project_list_item->id . '_' . $unitId . '_' . $date;
                            if (!Redis::exists($notifyKey)) {
                                sendStoreNumberNotification($project_list_item, $adgroupInfo, $formCount, $project_list_item->storenumber, $project, $admins, '表单数量达到阈值，自动暂停');

                                // 设置企微通知缓存，防止重复发送
                                Redis::setex($notifyKey, 86400, 1); // 24小时过期

                                Log::channel('storenumer')->info('百度账号[' . $ucName . ']企微通知发送成功，单元: ' . $unitId);
                            } else {
                                Log::channel('storenumer')->info('百度账号[' . $ucName . ']企微通知已发送过，跳过，单元: ' . $unitId);
                            }

                            Log::channel('storenumer')->info('百度账号[' . $ucName . ']单元[' . $unitId . ']已暂停，表单数量: ' . $formCount . ', 阈值: ' . $project_list_item->storenumber);
                        } else {
                            Log::channel('storenumer')->error('百度账号[' . $ucName . ']单元[' . $unitId . ']暂停失败');
                        }
                    } else {
                        Log::channel('storenumer')->info('百度账号[' . $ucName . ']单元[' . $unitId . ']已经是暂停状态');
                    }
                } else {
                    Log::channel('storenumer')->info('百度账号[' . $ucName . ']单元[' . $unitId . ']表单数量未达到阈值: ' . $formCount . ' < ' . $project_list_item->storenumber);
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::channel('storenumer')->error('处理表单失败: ' . $e->getMessage());
            return false;
        }
    }
}
if (!function_exists('getFormDataByDate')) {
    /**
     * 获取指定日期的表单数据
     * @param string $ucName 账号名称
     * @param string $date 日期
     * @return array
     */
    function getFormDataByDate($ucName, $date)
    {
        try {
            $data = Db::connection('mysql')->table('ad_clue')
                ->select('unitId', 'userAgent', 'created_at')
                ->where('ucName', $ucName)
                ->whereRaw('LENGTH(unitId) > 5')
                ->whereDate('created_at', $date)
                ->get()
                ->toArray();
            return $data;
        } catch (\Exception $e) {
            Log::channel('storenumer')->error('查询表单数据失败: ' . $e->getMessage());
            return [];
        }
    }
}
if (!function_exists('getUnitFormCounts')) {
    /**
     * 获取指定单元的表单总数（过滤刷量数据）
     * @param array $formData 表单数据
     * @return array 按单元ID分组的表单数量
     */
    function getUnitFormCounts($formData)
    {
        $unitCounts = [];

        foreach ($formData as $item) {
            $unitId = $item->unitId;

            if (!isset($unitCounts[$unitId])) {
                $unitCounts[$unitId] = 0;
            }

            // 检查userAgent字段，调用detectPhoneBrand方法判断是否是刷量的手机
            if (isset($item->userAgent)) {
                $brand = detectPhoneBrand($item->userAgent);

                // 如果不是刷量手机，则计入总数
                if ($brand == '百度') {
                    $unitCounts[$unitId]++;
                }
            } else {
                // 如果没有userAgent字段，默认计入总数
                $unitCounts[$unitId]++;
            }
        }

        return $unitCounts;
    }
}
if (!function_exists('checkUnitPauseStatus')) {
    /**
     * 检查单元是否已经暂停
     * @param string $ucName 账号名称
     * @param string $unitId 单元ID
     * @param object $project 项目信息
     * @return bool
     */
    function checkUnitPauseStatus($ucName, $unitId, $project)
    {
        try {
            global $GuzzleClient;

            // 调用百度API检查单元状态
            $payload = [
                'header' => [
                    'userName' => $project->userName,
                    'accessToken' => $project->accessToken,
                    'action' => 'API-PYTHON'
                ],
                'body' => [
                    'adgroupFeedIds' => [$unitId]
                ]
            ];

            $response = $GuzzleClient->post('https://api.baidu.com/json/feed/v1/AdgroupFeedService/getAdgroupFeed', [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            // 检查API响应
            if (isset($result['header']['status']) && $result['header']['status'] == 0) {
                if (isset($result['body']['data'][0]['adgroupFeedStatus'])) {
                    // 1表示暂停状态，0表示启用状态
                    return $result['body']['data'][0]['adgroupFeedStatus'] == 1;
                }
            }

            return false;

        } catch (\Exception $e) {
            Log::channel('storenumer')->error('检查单元状态失败: ' . $e->getMessage());
            return false;
        }
    }
}

