<?php
//信息流账户 展现 点击 消费 点击率	等查询 查前30天的  每三小时更新一次，更新baidu_xinxiliu_subuser表里的数据
namespace app\crontab;

use DateTime;
use support\Db;
use support\Log;

// 记录开始时间
$startTime = microtime(true);
DB::table('baidu_xinxiliu_subuser')
    ->leftJoin('baidu_xinxiliu_refreshToken', 'baidu_xinxiliu_subuser.masterUid', '=', 'baidu_xinxiliu_refreshToken.userId')
    ->select(
        'baidu_xinxiliu_subuser.id', 'baidu_xinxiliu_subuser.userName',
        'baidu_xinxiliu_refreshToken.accessToken',
        'baidu_xinxiliu_refreshToken.id as rid'
    )
    ->orderBy('baidu_xinxiliu_subuser.id')
    ->chunk(200, function ($baidu_xinxiliu_refreshToken) {
        foreach ($baidu_xinxiliu_refreshToken as $xinxiliu_refreshToken) {
            //今天
            $today = date('Y-m-d');
            //一个月以前
            $day30 = date('Y-m-d', strtotime('-30 day'));
            // 设置循环开始日期为当月的第一天
            $startDay = new DateTime("first day of this month");
            //月初第一天
            $firstdayDate = $startDay->format('Y-m-d');
            $user_payload = array(
                "header" => array(
                    "userName" => $xinxiliu_refreshToken->userName,
                    "accessToken" => $xinxiliu_refreshToken->accessToken,
                    "action" => "API-PYTHON"
                ),
            );
            $user_payload['body'] = [
                "reportType" => 2172649,
                "startDate" => $day30,
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
            $reportData = getReportData($jsonData);
            if (is_array($reportData) && $reportData['header']['desc'] == 'success') {
                if (isset($reportData['body']['data'][0]['rowCount']) && $reportData['body']['data'][0]['rowCount'] >= 1) {
                    foreach ($reportData['body']['data'][0]['rows'] as $data) {
                        //Db::connection()->enableQueryLog();
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
                } else {
                    // $log->info('更新baidu_xinxiliu_subuser表里' . $xinxiliu_refreshToken->userName . ' 展 点 消 失败，DataReport2.php 暂无数据' . date("Y-m-d H:i:s", time()) . PHP_EOL);
                }
            } else {
                // $log->info('更新baidu_xinxiliu_subuser表里' . $xinxiliu_refreshToken->userName . ' 展 点 消 失败，DataReport1.php' . date("Y-m-d H:i:s", time()) . PHP_EOL);
            }

        }
    });

$endTime = microtime(true);
$executionTime = $endTime - $startTime;
Log::channel('crontab')->info('更新展现 点击 消费 点击率等数据完毕,DataReport2.php 代码运行时间: ' . $executionTime . "秒，___时间：" . date("Y-m-d H:i:s", time()) . PHP_EOL);