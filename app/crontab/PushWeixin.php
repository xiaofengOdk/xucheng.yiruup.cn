<?php
//余额不足，企业微信通知。
namespace app\crontab;

use app\model\Project;
use GuzzleHttp\Client as GuzzleHttp1;
use GuzzleHttp\Promise\Utils;
use support\Db;
use support\Log;
use support\Redis;

// 记录开始时间
$startTime = microtime(true);

$baidu_xinxiliu_refleToken = DB::table('baidu_xinxiliu_refreshToken')->get();
$baidu_xinxiliu_refleToken_a = [];
foreach ($baidu_xinxiliu_refleToken as $item) {
    $baidu_xinxiliu_refleToken_a[$item->userId] = $item;
}
DB::table('baidu_xinxiliu_subuser')
    ->where('userName', 'R-天开文运')
    ->orderBy('id', 'desc')->chunkById(50, function ($baidu_xinxiliu_subuser) use ($baidu_xinxiliu_refleToken_a,) {
    $guzzleClient = new GuzzleHttp1(['timeout' => 15.0]);
    //异步请求的 url 参数数组
    $guzzlePromise = [];
    foreach ($baidu_xinxiliu_subuser as $k => $xinxiliu_subuser) {
        var_dump($xinxiliu_subuser);
        $project = new Project;
        $project = $project->get_projectBysubName($xinxiliu_subuser->userName, $xinxiliu_subuser->adminId);
        var_dump($project);
        //企业微信通知 当余额小于600的时候 提醒，只提醒一次
        if ($xinxiliu_subuser->balance <= 600 && $xinxiliu_subuser->status == 1) {
            //是否提醒过
            $is_push = Redis::get('weixin_push_600is_' . $xinxiliu_subuser->userId);
            if (!$is_push) {
                $weixin_message = '';
                $weixin_message .= "信息流账户：<font color=\"info\">" . $xinxiliu_subuser->userName . "</font> 余额不足。
                     >现余额为<font color=\"info\">" . $xinxiliu_subuser->balance . "</font>币。
                     >低于预警余额<font color=\"comment\">600币</font>。
                     >请<font color=\"info\">相关同事注意</font>。
                     >时间:" . date("Y-m-d H:i:s") . "\n";
                if (isset($project[$xinxiliu_subuser->userName])) {
                    if (isset($project[$xinxiliu_subuser->userName]['sellName']) && $project[$xinxiliu_subuser->userName]['sellName'] != false) {
                        $weixin_message .= ">项目：<font color=\"comment\">" . $project[$xinxiliu_subuser->userName]['clientName'] . "</font>。
                            >销售: <font color=\"info\">" . $project[$xinxiliu_subuser->userName]['sellName'] . "</font>";
                    }
                    $weixin_message .= ">优化师: <font color=\"info\">" . $project[$xinxiliu_subuser->userName]['youhuashiName'] . "</font>。\n";
                    if ($project[$xinxiliu_subuser->userName]['weixin'] != null) $weixin_message .= "<@" . $project[$xinxiliu_subuser->userName]['weixin'] . ">";
                    //给销售发通知
                    if (isset($project[$xinxiliu_subuser->userName]['weixin_push']) && $project[$xinxiliu_subuser->userName]['weixin_push'] != null)
                        push_work_weixin($project[$xinxiliu_subuser->userName]['weixin_push'], $weixin_message);
                }
                push_work_weixin('4742b357-8c97-4f7c-9702-86c7986cdf9c', $weixin_message);
                //只提醒一次
                Redis::set('weixin_push_600is_' . $xinxiliu_subuser->userId, 1);
            }

        }
        //当余额大于600的时候 解除提醒限制
        if ($xinxiliu_subuser->balance > 600 && $xinxiliu_subuser->status == 1) {
            Redis::del('weixin_push_600is_' . $xinxiliu_subuser->userId);
        }
        //企业微信通知 当余额等于0的时候 提醒，只提醒一次
        if ($xinxiliu_subuser->balance == 0 && $xinxiliu_subuser->status == 1) {
            //是否提醒过
            $is_push = Redis::get('weixin_push_0is_' . $xinxiliu_subuser->userId);
            if (!$is_push) {

                $weixin_message = "信息流账户：<font color=\"info\">" . $xinxiliu_subuser->userName . "</font><font color=\"warning\"> 账户余额已经归0</font>。
                    >请<font color=\"info\">相关同事注意</font>。
                    >时间: " . date("Y-m-d H:i:s") . "。\n";
                if (isset($project[$xinxiliu_subuser->userName])) {
                    if (isset($project[$xinxiliu_subuser->userName]['clientName'])) {
                        $weixin_message .= ">项目：<font color=\"comment\">";
                        $weixin_message .= $project[$xinxiliu_subuser->userName]['clientName'] . "</font>。";
                    }
                    $weixin_message .= ">销售: <font color=\"info\">" . $project[$xinxiliu_subuser->userName]['sellName'] . "</font>>优化师: <font color=\"info\">" . $project[$xinxiliu_subuser->userName]['youhuashiName'] . "</font>。\n";
                    if ($project[$xinxiliu_subuser->userName]['weixin'] != null) $weixin_message .= "<@" . $project[$xinxiliu_subuser->userName]['weixin'] . ">";
                    //给销售发通知
                    if (isset($project[$xinxiliu_subuser->userName]['weixin_push']) && $project[$xinxiliu_subuser->userName]['weixin_push'] != null)
                        push_work_weixin($project[$xinxiliu_subuser->userName]['weixin_push'], $weixin_message);
                }
                push_work_weixin('4742b357-8c97-4f7c-9702-86c7986cdf9c', $weixin_message);
                //只提醒一次
                Redis::set('weixin_push_0is_' . $xinxiliu_subuser->userId, 1);
            }

        }
        //当余额大于0的时候 解除提醒限制
        if ($xinxiliu_subuser->balance > 0 && $xinxiliu_subuser->status == 1) {
            Redis::del('weixin_push_0is_' . $xinxiliu_subuser->userId);
        }

        $user_payload = array(
            "header" => array(
                "userName" => $xinxiliu_subuser->userName,
                "accessToken" => $baidu_xinxiliu_refleToken_a[$xinxiliu_subuser->masterUid]->accessToken,
                "action" => "API-PYTHON"
            ),
        );

        //信息流账户余额查询
        $user_payload['body'] = array(
            "accountFeedFields" => array(
                "userId",
                "balance",
                "budget",
                "balancePackage",
                "userStat",
                "uaStatus",
                "validFlows",
                "cid",
                "liceName",
                "tradeId",
                "budgetOfflineTime",
                "adtype"
            ));
        $jsonData = json_encode($user_payload);
        $getAccountFeedUrl = 'https://api.baidu.com/json/feed/v1/AccountFeedService/getAccountFeed';
        // 创建一组异步请求
        $guzzlePromise['request' . $k] = $guzzleClient->postAsync($getAccountFeedUrl, ['json' => $user_payload]);
    }
    // 并发发送请求并等待所有请求完成
    $results = Utils::settle($guzzlePromise)->wait();
    // 处理每个请求的结果
    $responseData = [];
    foreach ($results as $key => $result) {
        if ($result['state'] === 'fulfilled') {
            $AccountFeedData = json_decode($result['value']->getBody()->getContents(), true);
            if (is_array($AccountFeedData) && $AccountFeedData['header']['desc'] == 'success') {
                $u = [
                    'balancePackage' => $AccountFeedData['body']['data'][0]['balancePackage'],
                    'validFlows' => json_encode($AccountFeedData['body']['data'][0]['validFlows']),
                    'tradeId' => $AccountFeedData['body']['data'][0]['tradeId'],
                    'budgetOfflineTime' => json_encode($AccountFeedData['body']['data'][0]['budgetOfflineTime']),
                    'cid' => $AccountFeedData['body']['data'][0]['cid'],
                    'liceName' => $AccountFeedData['body']['data'][0]['liceName'],
                    'balance' => $AccountFeedData['body']['data'][0]['balance'],
                    'budget' => $AccountFeedData['body']['data'][0]['budget'],
                    'userStat' => $AccountFeedData['body']['data'][0]['userStat'],
                    'uaStatus' => $AccountFeedData['body']['data'][0]['uaStatus'],
                    'adtype' => $AccountFeedData['body']['data'][0]['adtype'],
                    'updated_at' => date("Y-m-d H:i:s", time()),
                ];
                // echo  $AccountFeedData['body']['data'][0]['userId'].'。。。。。余额更新成功,现余额为'.$AccountFeedData['body']['data'][0]['balance'].'币'.PHP_EOL;
                $updateId = DB::table('baidu_xinxiliu_subuser')
                    ->where('userId', $AccountFeedData['body']['data'][0]['userId'])
                    ->update($u);
            } else {
                // var_dump($AccountFeedData);
                // Log::channel('pushweixin')->error('error PushWeixin.php 请求百度接口失败:'. $xinxiliu_subuser->userName . ' userId' . $xinxiliu_subuser->userId .json_encode($AccountFeedData ));
            }
        } else {
            // 请求失败
            $exception = $result['reason'];
            $responseData[$key] = [
                'error' => $exception->getMessage(),
            ];
            // Log::channel('pushweixin')->error('网络请求错误 PushWeixin.php 请求百度接口失败:'. $xinxiliu_subuser->userName . ' userId' . $xinxiliu_subuser->userId . $exception->getMessage() . __LINE__);
        }
    }
});
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
Log::channel('pushweixin')->info('余额不足，企业微信通知等数据完毕,PushWeixin1.php 代码运行时间: ' . $executionTime . "秒");
