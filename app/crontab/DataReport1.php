<?php
//信息流账户 展现 点击 消费 点击率	等查询  每五分钟更新一次，更新baidu_xinxiliu_subuser表里的数据
namespace app\crontab;

use app\model\Project;
use support\Db;
use support\Log;
use support\Redis;
use Workerman\Http\Client;

// 记录开始时间
$startTime = microtime(true);
$options = [
    'max_conn_per_addr' => 512, // 每个域名最多维持多少并发连接
    'keepalive_timeout' => 15,  // 连接多长时间不通讯就关闭
    'connect_timeout' => 30,  // 连接超时时间
    'timeout' => 30,  // 请求发出后等待响应的超时时间
];
$http = new Client($options);

$baidu_xinxiliu_refleToken = DB::table('baidu_xinxiliu_refreshToken')->get();
$baidu_xinxiliu_refleToken_a = [];
foreach ($baidu_xinxiliu_refleToken as $item) {
    $baidu_xinxiliu_refleToken_a[$item->userId] = $item;
}

DB::table('baidu_xinxiliu_subuser')->chunkById(30, function ($baidu_xinxiliu_subuser) use ($baidu_xinxiliu_refleToken_a, $http) {
    foreach ($baidu_xinxiliu_subuser as $xinxiliu_subuser) {
        $user_payload = array(
            "header" => array(
                "userName" => $xinxiliu_subuser->userName,
                "accessToken" => $baidu_xinxiliu_refleToken_a[$xinxiliu_subuser->masterUid]->accessToken,
                "action" => "API-PYTHON"
            ),
        );
        $today = date("Y-m-d");
        $yesterday = date("Y-m-d", strtotime("-1 day"));
        $user_payload['body'] = [
            "reportType" => 2172649,
            "startDate" => $yesterday,
            "endDate" => $today,
            "timeUnit" => "DAY",
            "columns" => ["date", "userId", "userName", "impression", "click", "cost", "ctr", "cpc", "cpm", "phoneButtonClicks"],
            "sorts" => [],
            "filters" => [],
            "startRow" => 0,
            "rowCount" => 200,
            "needSum" => false
        ];
        $jsonData = json_encode($user_payload);
        $refreshTokenUrl = 'https://api.baidu.com/json/sms/service/OpenApiReportService/getReportData';
        $project = new Project;
        $project = $project->get_projectBysubName($xinxiliu_subuser->userName,$xinxiliu_subuser->adminId);
        $http->request($refreshTokenUrl, [
            'method' => 'POST',
            'version' => '1.1',
            'headers' => [
                'Connection' => 'keep-alive',
                'Content-Type' => 'application/json',
                'User-Agent' => 'Baiduspider+(+http://www.baidu.com/search/spider.html)',
                'Authorization' => 'Bearer sk-xxx',
            ],
            'data' => json_encode($user_payload),
            'success' => function ($response) use ($xinxiliu_subuser, $yesterday, $project) {
                if ($response->getStatusCode() == 200 && $response->getBody()->getSize() > 0) {
                    $reportData = json_decode($response->getBody()->getContents(), true);
                    if (is_array($reportData) && $reportData['header']['desc'] == 'success') {
                        if (isset($reportData['body']['data'][0]['rowCount']) && $reportData['body']['data'][0]['rowCount'] >= 1) {
                            foreach ($reportData['body']['data'][0]['rows'] as $data) {
                                //企业微信通知 当余额小于昨日消耗的时候 提醒，只提醒一次
                                if ($data['date'] == $yesterday) {
                                    if ($xinxiliu_subuser->status == 1 && $xinxiliu_subuser->balance < $data['cost']) {
                                        //是否提醒过
                                        $is_push = Redis::get('weixin_push_costis_' . $xinxiliu_subuser->userId);
                                        if (!$is_push) {
                                            $weixin_message= "信息流账户：<font color=\"info\">" . $xinxiliu_subuser->userName . "</font> 现余额为<font color=\"info\">" . $xinxiliu_subuser->balance . "</font>币。
                                             >低于昨日消耗<font color=\"comment\">" . $data['cost'] . "币</font>。
                                             >请<font color=\"info\">相关同事注意</font>。
                                             >时间: " . date("Y-m-d H:i:s") . "。\n";
                                            if (isset($project[$xinxiliu_subuser->userName])) {
                                                $weixin_message.=">项目：<font color=\"comment\">" . $project[$xinxiliu_subuser->userName]['clientName'] . "</font>。\n";
                                                if(isset($project[$xinxiliu_subuser->userName]['sellName'])&&$project[$xinxiliu_subuser->userName]['sellName']!=false){
                                                    $weixin_message .= ">销售: <font color=\"info\">" . $project[$xinxiliu_subuser->userName]['sellName'] . "， </font>";
                                                }
                                                $weixin_message .= ">优化师: <font color=\"info\">" . $project[$xinxiliu_subuser->userName]['youhuashiName'] . "</font>。\n";
                                                if ($project[$xinxiliu_subuser->userName]['weixin'] != null) $weixin_message .= "<@" . $project[$xinxiliu_subuser->userName]['weixin'] . ">";
                                            }
                                            push_work_weixin('4742b357-8c97-4f7c-9702-86c7986cdf9c', $weixin_message);
                                            //只提醒一次
                                            Redis::set('weixin_push_costis_' . $xinxiliu_subuser->userId, 1);
                                        }

                                    }
                                    //当余额大于昨日消耗的时候 解除提醒限制
                                    if ($xinxiliu_subuser->status == 1 && $xinxiliu_subuser->balance > $data['cost']) {
                                        Redis::del('weixin_push_costis_' . $xinxiliu_subuser->userId);
                                    }
                                }
                                $is_exist = Db::table('baidu_xinxiliu_reportdata')
                                    ->where('userId', $data['userId'])
                                    ->where('eventDate', $data['date'])
                                    ->exists();
                                if ($is_exist) {
                                    DB::table('baidu_xinxiliu_reportdata')
                                        ->where('userId', $data['userId'])
                                        ->where('eventDate', $data['date'])
                                        ->update([
                                            'impression' => isset($data['impression']) ? $data['impression'] : 0,
                                            'click' => isset($data['click']) ? $data['click'] : 0,
                                            'cost' => isset($data['cost']) ? $data['cost'] : 0,
                                            'ctr' => isset($data['ctr']) ? $data['ctr'] : 0,
                                            'cpc' => isset($data['cpc']) ? $data['cpc'] : 0,
                                            'cpm' => isset($data['cpm']) ? $data['cpm'] : 0,
                                            'phoneButtonClicks' => isset($data['phoneButtonClicks']) ? $data['phoneButtonClicks'] : 0,
                                            'updated_at' => date("Y-m-d H:i:s", time()),
                                        ]);
                                } else {
                                    DB::table('baidu_xinxiliu_reportdata')
                                        ->insert([
                                            'userId' => $data['userId'],
                                            'eventDate' => $data['date'],
                                            'impression' => isset($data['impression']) ? $data['impression'] : 0,
                                            'click' => isset($data['click']) ? $data['click'] : 0,
                                            'cost' => isset($data['cost']) ? $data['cost'] : 0,
                                            'ctr' => isset($data['ctr']) ? $data['ctr'] : 0,
                                            'cpc' => isset($data['cpc']) ? $data['cpc'] : 0,
                                            'cpm' => isset($data['cpm']) ? $data['cpm'] : 0,
                                            'phoneButtonClicks' => isset($data['phoneButtonClicks']) ? $data['phoneButtonClicks'] : 0,
                                            'updated_at' => date("Y-m-d H:i:s", time()),
                                            'created_at' => date("Y-m-d H:i:s", time()),
                                        ]);
                                }
                            }
                        }
                    } else {
                        // Log::channel('crontab')->error('Datareport1.php '.$xinxiliu_subuser->userName.' userId'.$xinxiliu_subuser->userId.' 百度接口返回出问题需排查 ' . json_encode($reportData) . PHP_EOL);
                    }
                } else {
                    Log::channel('crontab')->info('Datareport1.php 请求百度接口失败:' . $response->getBody()->getContents());
                }
            },
            'error' => function ($exception) {
                Log::channel('crontab')->info('error Datareport1.php 请求百度接口失败:' . $exception->getMessage());
            }
        ]);

    }
});
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
Log::channel('crontab')->info('更新展现 点击 消费 点击率等数据完毕,DataReport1.php 代码运行时间: ' . $executionTime . "秒");