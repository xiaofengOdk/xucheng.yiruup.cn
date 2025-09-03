<?php

namespace plugin\admin\app\model;

use GuzzleHttp\Client as GuzzleClient;
use function Symfony\Component\Console\Style\write;

class CampaginBaidu extends Base 
{
    protected $GuzzleClient;
    function __construct(array $attributes = [])
    {
        $this->GuzzleClient =new GuzzleClient();
        parent::__construct($attributes);
    }

    function getCampaignFeedone($request, $userInfo , $accessToken ,$unselectedRanges)
    {
        $url = 'https://api.baidu.com/json/feed/v1/CampaignFeedService/getCampaignFeed';
        //header
        $user_payload = array(
            "header" => array(
                "userName" => $userInfo['userName'],
                "accessToken" => $accessToken,
                "action" => "API-PYTHON"
            ),
        );
        //body
        $user_payload['body'] = [
            'campaignFeedFields' => ['campaignFeedId', 'schedule', 'starttime', 'endtime'],  // 添加starttime和endtime字段
        ];
        try {
            // 发送异步请求并等待响应
            $response = $this->GuzzleClient->postAsync($url, ['json' => $user_payload])->wait();
            $result = json_decode($response->getBody(), true);
            // 验证响应是否成功
            if (isApiResponseSuccess($result)) {
                // 提取campaignFeedId和schedule数据
                $campaignFeedIds = extractCampaignFeedIds($result);
                if(count($campaignFeedIds) > 0) {
                    // 获取schedule数据
                    $schedule = [];
                    if (isset($result['body']['data']) && is_array($result['body']['data'])) {
                        foreach ($result['body']['data'] as $item) {
                            if (isset($item['schedule']) && is_array($item['schedule'])) {
                                // 处理每个时间段的schedule数据
                                foreach ($item['schedule'] as $timeSlot) {
                                    if (isset($timeSlot['weekDay']) && isset($timeSlot['startHour']) && isset($timeSlot['endHour'])) {
                                        // 按照前端期望的格式 [endHour, startHour, weekDay] 构建数据
                                        $schedule[] = [
                                            (int)$timeSlot['endHour'],
                                            (int)$timeSlot['startHour'],
                                            (int)$timeSlot['weekDay']
                                        ];
                                    }
                                }
                            }
                        }
                    }
                    
                    // 获取starttime和endtime数据
                    $starttime = null;
                    $endtime = null;
                    if (isset($result['body']['data']) && is_array($result['body']['data'])) {
                        foreach ($result['body']['data'] as $item) {
                            if (isset($item['starttime'])) {
                                $starttime = $item['starttime'];
                            }
                            if (isset($item['endtime'])) {
                                $endtime = $item['endtime'];
                            }
                            // 只取第一个项目的数据
                            break;
                        }
                    }
                    
                    return [
                        'schedule' => $schedule,
                        'campaignFeedIds' => $campaignFeedIds,
                        'starttime' => $starttime,
                        'endtime' => $endtime
                    ];
                } else {
                    // 友好提示：账户下没有新建计划
                    throw new \Exception(($userInfo['userName'] ?? '该账户') . '没有新建计划');
                }
            } else {
                // 处理API错误响应
                $errorMsg = getApiErrorMessage($result);

                @writelog($errorMsg, 'get');
                throw new \Exception('API请求失败: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            // 记录异常日志并直接抛出原始异常消息
            $errorMsg = $e->getMessage();
            @writelog($errorMsg, 'get');
            throw new \Exception($errorMsg, 0);
        }
    }


    function getCampaignFeed($request, $userInfo , $accessToken ,$unselectedRanges, $starttime = null, $endtime = null, $pause = null, $isDelete = false)
    {
        $url = 'https://api.baidu.com/json/feed/v1/CampaignFeedService/getCampaignFeed';
        //header
        $user_payload = array(
            "header" => array(
                "userName" => $userInfo['userName'],
                "accessToken" => $accessToken,
                "action" => "API-PYTHON"
            ),
        );
        //body
        $user_payload['body'] = [
            'campaignFeedFields' => ['campaignFeedId'],
        ];
        try {
            // 发送异步请求并等待响应
            $response = $this->GuzzleClient->postAsync($url, ['json' => $user_payload])->wait();
            $result = json_decode($response->getBody(), true);
            // 验证响应是否成功
            if (isApiResponseSuccess($result)) {
                // 提取符合条件的campaignFeedId
                $campaignFeedIds = extractCampaignFeedIds($result);
                if(count($campaignFeedIds) > 0) {
                    // 根据操作类型调用不同的接口
                    if ($isDelete) {
                        // 删除计划操作
                        $deleteResult = $this->deleteCampaignFeed($request, $userInfo, $accessToken, $campaignFeedIds);
                        
                        if ($deleteResult === true) {
                            return [
                                'success' => true,
                                'message' => '删除成功',
                                'campaignFeedIds' => $campaignFeedIds
                            ];
                        } else {
                            throw new \Exception('删除推广计划失败');
                        }
                    } else {
                        //调用updateCampaignFeed接口
                        $updateResult = $this->updateCampaignFeed($request, $userInfo, $accessToken, $campaignFeedIds, $unselectedRanges, $starttime, $endtime, $pause);

                        if ($updateResult === true) {
                            return [
                                'success' => true,
                                'message' => '更新成功',
                                'campaignFeedIds' => $campaignFeedIds
                            ];
                        } else {
                            throw new \Exception('更新推广计划失败');
                        }
                    }
                } else {
                    throw new \Exception('未找到可用的推广计划');
                }
            } else {
                // 处理API错误响应
                $errorMsg = getApiErrorMessage($result);
                @writelog($errorMsg, 'get');
                throw new \Exception('获取推广计划失败: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            // 记录异常日志并直接抛出原始异常消息
            $errorMsg = $e->getMessage();
            @writelog($errorMsg, 'get');
            throw new \Exception($errorMsg, 0);
        }
    }



    function updateCampaignFeed($request, $userInfo, $accessToken, $campaignFeedId, $unselectedRanges, $starttime = null, $endtime = null, $pause = null)
    {
        $url = 'https://api.baidu.com/json/feed/v1/CampaignFeedService/updateCampaignFeed';

        // 构建请求数据
        $campaignFeedTypes_array = [];
        foreach ($campaignFeedId as $k => $v) {
            $campaignFeedTypes_array[$k] = [
                "campaignFeedId" => $v,
            ];
            
            // 如果提供了unselectedRanges，则添加schedule
            if ($unselectedRanges !== null) {
                // 确保 $unselectedRanges 是数组
                $scheduleArray = is_string($unselectedRanges) ? json_decode($unselectedRanges, true) : $unselectedRanges;

                if (!is_array($scheduleArray)) {
                    throw new \Exception('时间段数据格式错误');
                }

                $formattedSchedule = array_map(function($item) {
                    return [
                        'endHour' => $item[0],
                        'startHour' => $item[1],
                        'weekDay' => $item[2]
                    ];
                }, $scheduleArray);
                
                $campaignFeedTypes_array[$k]["schedule"] = $formattedSchedule;
            }
            
            // 如果提供了starttime和endtime，则添加到请求中
            if ($starttime !== null && $endtime !== null) {
                $campaignFeedTypes_array[$k]["starttime"] = $starttime;
                $campaignFeedTypes_array[$k]["endtime"] = $endtime;
            }
            
            // 如果提供了pause参数，则添加pause字段
            if ($pause !== null) {
                $campaignFeedTypes_array[$k]["pause"] = $pause;
            }
        }
        // 修正请求结构，符合百度API要求
        $user_payload = [
            "header" => [
                "userName" => $userInfo['userName'],
                "accessToken" => $accessToken,
                "methodName" => "updateCampaignFeed",
                "target" => "json"
            ],
            "body" => [
                "campaignFeedTypes" => $campaignFeedTypes_array
            ]
        ];

        try {
            // 发送异步请求并等待响应
            $response = $this->GuzzleClient->postAsync($url, ['json' => $user_payload])->wait();
            $result = json_decode($response->getBody(), true);

            // 验证响应是否成功
            if (isApiResponseSuccess($result)) {
                // 处理成功响应
                if (isset($result['body']['data']) && is_array($result['body']['data'])) {
//                    var_dump($result['body']['data']);
                    @writelog($result['body']['data'], 'update');
                    return true;
                }
            } else {
                // 处理API错误响应
                $errorMsg = getApiErrorMessage($result);
                @writelog($errorMsg, 'update');
                throw new \Exception('更新推广时间失败: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            // 记录异常日志并直接抛出原始异常消息
            $errorMsg = $e->getMessage();
            @writelog($errorMsg, 'update');
            throw new \Exception($errorMsg, 0);
        }
    }

    /**
     * 删除推广计划
     * @param $request
     * @param $userInfo
     * @param $accessToken
     * @param $campaignFeedIds
     * @return bool
     * @throws \Exception
     */
    function deleteCampaignFeed($request, $userInfo, $accessToken, $campaignFeedIds)
    {
        $url = 'https://api.baidu.com/json/feed/v1/CampaignFeedService/deleteCampaignFeed';

        // 构建请求数据 - 删除操作直接使用campaignFeedIds数组
        $campaignFeedIds_array = [];
        foreach ($campaignFeedIds as $v) {
            $campaignFeedIds_array[] = $v;
        }

        // 构建请求结构 - 删除操作使用campaignFeedIds字段
        $user_payload = [
            "header" => [
                "userName" => $userInfo['userName'],
                "accessToken" => $accessToken,
                "action" => "API-PYTHON"
            ],
            "body" => [
                "campaignFeedIds" => $campaignFeedIds_array
            ]
        ];


        try {
            
            // 发送异步请求并等待响应
            $response = $this->GuzzleClient->postAsync($url, ['json' => $user_payload])->wait();
            $result = json_decode($response->getBody(), true);
            
            // 验证响应是否成功
            if (isApiResponseSuccess($result)) {
                // 处理成功响应
                if (isset($result['body']['data']) && is_array($result['body']['data'])) {
                    @writelog($result['body']['data'], 'delete');
                    return true;
                }
            } else {
                // 处理API错误响应
                $errorMsg = getApiErrorMessage($result);
                @writelog($errorMsg, 'delete');
                throw new \Exception('删除推广计划失败: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            // 记录异常日志并直接抛出原始异常消息
            $errorMsg = $e->getMessage();
            @writelog($errorMsg, 'delete');
            throw new \Exception($errorMsg, 0);
        }
    }


 
 function getCampaignFeedCrontab($request, $userInfo , $accessToken ,$unselectedRanges, $starttime = null, $endtime = null, $pause = null, $isDelete = false)
    {
        $url = 'https://api.baidu.com/json/feed/v1/CampaignFeedService/getCampaignFeed';
        //header
        $user_payload = array(
            "header" => array(
                "userName" => $userInfo['userName'],
                "accessToken" => $accessToken,
                "action" => "API-PYTHON"
            ),
        );
        //body
        $user_payload['body'] = [
            'campaignFeedFields' => ['campaignFeedId', 'schedule', 'starttime', 'endtime','pause','status'],  // 添加starttime和endtime字段
        ];
        try {
            // 发送异步请求并等待响应
            $response = $this->GuzzleClient->postAsync($url, ['json' => $user_payload])->wait();
            $result = json_decode($response->getBody(), true);
            // 验证响应是否成功
            if (isApiResponseSuccess($result)) {
                // 提取符合条件的campaignFeedId
                $campaignFeedIds = extractCampaignFeedIds($result);
                if(count($campaignFeedIds) > 0) {
                    // 根据操作类型调用不同的接口
                    if ($isDelete) {
                        // 删除计划操作
                        $deleteResult = $this->deleteCampaignFeed($request, $userInfo, $accessToken, $campaignFeedIds);
                        
                        if ($deleteResult === true) {
                            return [
                                'code'=>0,
                                'success' => true,
                                'message' => '删除成功',
                                'campaignFeedIds' => $campaignFeedIds
                            ];
                        } else {
                            throw new \Exception('删除推广计划失败');
                        }
                    } else {
                    // 使用array_filter和count函数计算status为0的次数 如果大于0代表该账号已经停了 不需要进行更新

                        $pauseFalseCount = count(array_filter($result['body']['data'], function($item) {
                            return isset($item['status']) && $item['status'] ===0;
                        }));
                        
                        if ($pauseFalseCount >= 1) {
                            //调用updateCampaignFeed接口
                            $updateResult = $this->updateCampaignFeed($request, $userInfo, $accessToken, $campaignFeedIds, $unselectedRanges, $starttime, $endtime, $pause);

                            if ($updateResult === true) {
                                return [
                                    'code'=>0,
                                    'success' => true,
                                    'message' => '账户已拉停',
                                    'campaignFeedIds' => $campaignFeedIds
                                ];
                            } else {
                                return [
                                    'code'=>300,
                                    'success' => false,
                                    'message' => '更新推广计划失败',
                                    'campaignFeedIds' =>[] 
                                ];    
                            }
                        } else {
                            // pause为true，账号已停止，跳过更新
                            return [
                                'code'=>300,
                                'success' => false,
                                'message' => '账号已经是停止状态',
                                'campaignFeedIds' =>[] 
                            ];
                        }
                } 
                } else {
                    return [
                        'code'=>300,
                        'success' => false,
                        'message' => '该账号下没有新建计划',
                        'campaignFeedIds' =>[] 
                    ];            
                    }
            } else {
                // 处理API错误响应
                $errorMsg = getApiErrorMessage($result);
                @writelog($errorMsg, 'get');
                return [
                    'code'=>300,
                    'success' => false,
                    'message' => '获取推广计划失败',
                    'campaignFeedIds' =>[] 
                ];            
            }
        } catch (\Exception $e) {
            // 记录异常日志并直接抛出原始异常消息
            $errorMsg = $e->getMessage();
            @writelog($errorMsg, 'get');
            throw new \Exception($errorMsg, 0);
        }
    }




}
