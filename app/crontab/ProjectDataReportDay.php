<?php

namespace app\crontab;

use DateTime;
use support\Db;
use support\Log;
use Workerman\Http\Client;

// 记录开始时间
$startTime = microtime(true);
$options = [
    'max_conn_per_addr' => 512, // 每个域名最多维持多少并发连接
    'keepalive_timeout' => 30,  // 连接多长时间不通讯就关闭
    'connect_timeout' => 90,  // 连接超时时间
    'timeout' => 90,  // 请求发出后等待响应的超时时间
];
$http = new Client($options);
DB::table('baidu_xinxiliu_project')
    ->leftJoin('baidu_xinxiliu_subuser', 'baidu_xinxiliu_project.subName', '=', 'baidu_xinxiliu_subuser.userName')
    ->leftJoin('baidu_xinxiliu_refreshToken', 'baidu_xinxiliu_subuser.masterUid', '=', 'baidu_xinxiliu_refreshToken.userId')
    ->select(
        'baidu_xinxiliu_project.*',
        'baidu_xinxiliu_subuser.userId',
        'baidu_xinxiliu_subuser.userName',
        'baidu_xinxiliu_refreshToken.accessToken',
        'baidu_xinxiliu_subuser.id as sid',
        'baidu_xinxiliu_refreshToken.id as rid'
    )
    ->orderBy('baidu_xinxiliu_project.id')
    ->chunk(30, function ($baidu_xinxiliu_project) use ($http) {

        $inputDate = new DateTime();
        $inputDate->modify('-1 day');
        // 获取输入日期的年份和月份
        $year = $inputDate->format('Y');
        $month = $inputDate->format('m');

        // 设置循环开始日期为当月的第一天
        $startDay = new DateTime("first day of this month $year-$month");
        //月初第一天
        $firstdayDate = $startDay->format('Y-m-d');
        // 设置循环结束日期为今天
        $endDay = new DateTime('today');
        // 循环从开始日期到结束日期 保证数据库里有数据
        for ($date = $startDay; $date <= $endDay; $date->modify('+1 day')) {
            // 输出当前日期
            $curdate = $date->format('Y-m-d');
            //echo '开始日期' . $firstdayDate . " 当前日期" . $curdate . "\n";
            foreach ($baidu_xinxiliu_project as $project) {
                if ($project->userId != null) {
                    //当天数据要是没有就创建表数据
                    $is_exist = Db::table('baidu_xinxiliu_project_reportdata')
                        ->where('userId', $project->userId)
                        ->where('eventDate', $curdate)
                        ->exists();
                    //数据初始化
                    if (!$is_exist) {
                        Db::table('baidu_xinxiliu_project_reportdata')->insert(['eventDate' => $curdate, 'userId' => $project->userId, 'projectId' => $project->id, 'projectName' => $project->clientName]);
                    }
                }
            }
        }
        /*
           *  feedOCPCConversionsDetail3 表单提交成功量
              ctFeedOCPCConversionsDetail3 表单提交成功量（转化时间）
              aggrFormClickSuccess 表单按钮点击量
              ctAggrFormClickSuccess 表单按钮点击量（转化时间）
              weiXinCopyConversions 微信复制按钮点击量
              ctWeiXinCopyConversions 微信复制按钮点击量（转化时间）
              advisoryClueCount 留线索量
              ctAdvisoryClueCount 留线索量（转化时间）
              weixinFollowSuccessConversions	  微信加粉成功量
              ctWeixinFollowSuccessConversions	 微信加粉成功量（转化时间）
              validConsult 有效咨询量
              ctValidConsult 有效咨询量（转化时间）
              weixinAppInvokeUv 微信小程序调起人数
              ctWeixinAppInvokeUv 微信小程序调起人数（转化时间）
              monthCost 本月一号到昨天的消费
              monthFeedOCPCConversionsDetail3  本月一号到昨天的表单提交成功量
              monthWeiXinCopyConversions 本月一号到昨天的	微信复制按钮点击量
              monthAdvisoryClueCount 本月一号到昨天的 留线索量
              monthWeixinFollowSuccessConversions  本月一号到昨天的 微信加粉成功量
              monthPhoneDialUpConversions 本月一号到昨天的电话拨通量
          */

        $columns = ["date", "userId", "userName", "impression", "click", "cost", "ctr", "cpc", "cpm", "phoneButtonClicks", 'feedOCPCConversionsDetail3', 'ctFeedOCPCConversionsDetail3', 'phoneDialUpConversions', 'aggrFormClickSuccess', 'ctAggrFormClickSuccess', 'weiXinCopyConversions', 'ctWeiXinCopyConversions', 'advisoryClueCount', 'ctAdvisoryClueCount', 'weixinFollowSuccessConversions', 'ctWeixinFollowSuccessConversions', 'validConsult', 'ctValidConsult', 'weixinAppInvokeUv', 'ctWeixinAppInvokeUv'];
        $user_payload = array(
            "header" => array(
                "userName" => '',
                "accessToken" => '',
                "action" => "API-PYTHON"
            ),
        );
        //查昨天的数据
        $endDate = date('Y-m-d', strtotime("-1 day"));
        $user_payload['body'] = [
            "reportType" => 2172649,
            "startDate" => $firstdayDate,
            "endDate" => $endDate,
            "timeUnit" => "DAY",
            "columns" => $columns,
            "sorts" => [],
            "filters" => [],
            "startRow" => 0,
            "rowCount" => 2000,
            "needSum" => true
        ];
        foreach ($baidu_xinxiliu_project as $project) {
            if ($project->userId == null) {
                Log::channel('crontab')->info('更新项目数据 生成报表时，因为 ' . $project->clientName . '的Id为' . $project->id . '__userName为' . $project->clientName . '__账户还没授权，所以跳过。 ');
            } else {
                $user_payload['header']['userName'] = $project->userName;
                $user_payload['header']['accessToken'] = $project->accessToken;
                $refreshTokenUrl = 'https://api.baidu.com/json/sms/service/OpenApiReportService/getReportData';
                $http->request($refreshTokenUrl, [
                    'method' => 'POST',
                    'version' => '1.1',
                    'headers' => [
                        'Connection' => 'keep-alive',
                        'Content-Type: application/json;charset=utf-8',
                        'User-Agent' => 'Baiduspider+(+http://www.baidu.com/search/spider.html)',
                        'Authorization' => 'Bearer sk-xxx',
                    ],
                    'data' => json_encode($user_payload),
                    'success' => function ($response) use ($project, $endDate) {
                        if ($response->getStatusCode() == 200 && $response->getBody()->getSize() > 0) {
                            $reportData = json_decode($response->getBody()->getContents(), true);
                            if (is_array($reportData) && $reportData['header']['desc'] == 'success') {
                                if (is_array($reportData) && $reportData['header']['desc'] == 'success') {
                                    if (isset($reportData['body']['data'][0]['rowCount']) && $reportData['body']['data'][0]['rowCount'] >= 1) {
                                        foreach ($reportData['body']['data'][0]['rows'] as $row) {
                                            $u = [
                                                'impression' => isset($row['impression']) ? $row['impression'] : 0,
                                                'click' => isset($row['click']) ? $row['click'] : 0,
                                                'cost' => isset($row['cost']) ? $row['cost'] : 0,
                                                'ctr' => isset($row['ctr']) ? $row['ctr'] : 0,
                                                'cpc' => isset($row['cpc']) ? $row['cpc'] : 0,
                                                'cpm' => isset($row['cpm']) ? $row['cpm'] : 0,
                                                'feedOCPCConversionsDetail3' => $row['feedOCPCConversionsDetail3'],
                                                'aggrFormClickSuccess' => $row['aggrFormClickSuccess'],
                                                'weiXinCopyConversions' => $row['weiXinCopyConversions'],
                                                'ctWeiXinCopyConversions' => $row['ctWeiXinCopyConversions'],
                                                'phoneDialUpConversions' => $row['phoneDialUpConversions'],
                                                'advisoryClueCount' => $row['advisoryClueCount'],
                                                'validConsult' => $row['validConsult'],
                                                'weixinAppInvokeUv' => $row['weixinAppInvokeUv'],
                                                'weixinFollowSuccessConversions' => $row['weixinFollowSuccessConversions'],
                                                'updated_at' => date("Y-m-d H:i:s", time()),
                                            ];
                                            DB::table('baidu_xinxiliu_project_reportdata')
                                                ->where('userId', $row['userId'])
                                                ->where('eventDate', $row['date'])
                                                ->update($u);
                                        }
                                    }
                                    $summary = [
                                        'monthCost' => $reportData['body']['data'][0]['summary']['cost'] ?? 0,
                                        'monthFeedOCPCConversionsDetail3' => $reportData['body']['data'][0]['summary']['feedOCPCConversionsDetail3'] ?? 0,
                                        'monthWeiXinCopyConversions' => $reportData['body']['data'][0]['summary']['weiXinCopyConversions'] ?? 0,
                                        'monthAdvisoryClueCount' => $reportData['body']['data'][0]['summary']['advisoryClueCount'] ?? 0,
                                        'monthWeixinFollowSuccessConversions' => $reportData['body']['data'][0]['summary']['weixinFollowSuccessConversions'] ?? 0,
                                        'monthPhoneDialUpConversions' => $reportData['body']['data'][0]['summary']['phoneDialUpConversions'] ?? 0,
                                        'updated_at' => date("Y-m-d H:i:s", time()),
                                    ];
                                    //echo 'userId__'.$project->userId.'__eventDate'.$curdate.'summary'.PHP_EOL;;
                                    DB::table('baidu_xinxiliu_project_reportdata')
                                        ->where('userId', $project->userId)
                                        ->where('eventDate', $endDate)
                                        ->update($summary);
                                }
                            } else {
                                Log::channel('crontab')->error('ProjectDataReport.php ' . $project->userName . ' userId' . $project->userId . ' 百度接口返回出问题需排查 ' . json_encode($reportData) . PHP_EOL);
                            }
                        } else {
                            Log::channel('crontab')->error('ProjectDataReport.php 请求百度接口失败:' . $response->getBody()->getContents() . __LINE__);
                        }
                    },
                    'error' => function ($exception) {
                        Log::channel('crontab')->error('error ProjectDataReport.php 请求百度接口失败:' . $exception->getMessage() . __LINE__);
                    }
                ]);
            }

        }

    });
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
Log::channel('crontab')->info('更新项目数据 生成报表 完毕 PrejectDataReport.php 代码运行时间: ' . $executionTime . "秒");