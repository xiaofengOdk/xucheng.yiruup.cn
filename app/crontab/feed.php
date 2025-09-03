<?php
//信息流计划  复制 删除
namespace app\crontab;

use support\Db;
use support\Log;

//从 crontab/feed1.php 传参$userName
//$userName = 'RQYZ-旭日辉春';

$xinxiliu_subuser = DB::table('baidu_xinxiliu_subuser')->where('userName', $userName)->first();
$xinxiliu_refreshToken = DB::table('baidu_xinxiliu_refreshToken')->where('userId', $xinxiliu_subuser->masterUid)->first();

$user_payload = array(
    "header" => array(
        "userName" => $userName,
        "accessToken" => $xinxiliu_refreshToken->accessToken,
        "action" => "API-PYTHON"
    ),
);
$campaignData = [
    'campaignFeedId' => '信息流计划Id',
    'campaignFeedName' => '信息流计划名称',
    'subject' => '营销目标',
    'appinfo' => '推广app信息',
    'budget' => '推广计划预算',
    'starttime' => '推广开始时间',
    'endtime' => '推广结束时间',
    'schedule' => '推广计划暂停时段',
    'pause' => '计划启停',
    'status' => '推广计划状态',
    'bstype' => '物料类型',
    'campaignType' => '计划类型',
    'addtime' => '添加时间',
    'eshopType' => '交易所在平台',
    'shadow' => '计划影子的APP信息',
    'budgetOfflineTime' => '当天计划预算下线最近一次的时间',
    'rtaStatus' => '是否开通RTA',
    'bid' => '出价',
    'bidtype' => '出价方式',
    'ftypes' => '投放范围',
    'ocpc' => 'oCPC信息',
    'unefficientCampaign' => '低效计划',
    'campaignOcpxStatus' => '计划学习状态',
    'inheritAscriptionType' => '继承归属',
    'inheritUserids' => '继承优质计划账户id集合',
    'inheritCampaignInfos' => '继承优质计划的计划信息集合',
    'bmcUserId' => '商品中心用户ID',
    'catalogId' => '商品目录ID',
    'productType' => '产品库类型',
    'projectFeedId' => '项目ID',
    'useLiftBudget' => '是否开启一键起量',
    'liftBudget' => '起量预算',
    'liftStatus' => '起量状态',
    'deliveryType' => '投放场景',
    'appSubType' => '应用推广子类型',
    'miniProgramType' => '小程序子类型',
    'bidMode' => '出价模式',
    'productIds' => '产品ID',
    'saleType' => '营销场景',
    'liftBudgetSchedule' => '起量生效时间'
];
$campaignData = array_keys($campaignData);
$user_payload['body'] = [
    'campaignFeedFields' => $campaignData,
    'campaignFeedIds' => [],
];
$jsonData = json_encode($user_payload);
//计划数据
$feedData = getCampaignFeed($jsonData);
//如果计划数据查询成功
if ($feedData['header']['desc'] == 'success') {
    //var_dump($feedData['body']['data']);
    //计划Id 数组
    $campaignFeedIds = array_column($feedData['body']['data'], 'campaignFeedId');

    //新建计划
    if (is_array($feedData['body']['data']) && count($feedData['body']['data']) > 0) {
        //查询单元
        //$campaignFeed=getAdgroupFeed($user_payload,$campaignFeedIds);
        //批量新建计划的数组
        $data2 = [];
        //name 和 id 对应的数据
        $feed_name_ids = [];
        foreach ($feedData['body']['data'] as $k => $CampaignFeedType) {
            $data = [];
            $data['campaignFeedName'] = explode('__', $CampaignFeedType['campaignFeedName'])[0] . date("__md_H:i:s") . mt_rand(100, 1000);
            $data['campaignFeedName'] =truncateString($data['campaignFeedName']);
            $feed_name_ids[$data['campaignFeedName']] = $CampaignFeedType['campaignFeedId'];
            $CampaignFeedType['pause'] = true;
            isset($CampaignFeedType['subject']) && $data['subject'] = $CampaignFeedType['subject'];
            isset($CampaignFeedType['appinfo']) && $data['appinfo'] = $CampaignFeedType['appinfo'];
            isset($CampaignFeedType['budget']) && $data['budget'] = $CampaignFeedType['budget'];
            //计划启停	默认暂停
            $data['pause'] = true;

            //推广开始时间 当前时间往后一小时
            $data['starttime'] = null;
            //推广结束时间	当前时间往后半年
            isset($CampaignFeedType['endtime']) && $data['endtime'] = $CampaignFeedType['endtime'];
            isset($CampaignFeedType['schedule']) && $data['schedule'] = $CampaignFeedType['schedule'];

            isset($CampaignFeedType['bstype']) && $data['bstype'] = $CampaignFeedType['bstype'];
            isset($CampaignFeedType['campaignType']) && $data['campaignType'] = $CampaignFeedType['campaignType'];
            isset($CampaignFeedType['eshopType']) && $data['eshopType'] = $CampaignFeedType['eshopType'];
            isset($CampaignFeedType['ftypes']) && $data['ftypes'] = $CampaignFeedType['ftypes'];
            isset($CampaignFeedType['bidtype']) && $data['bidtype'] = $CampaignFeedType['bidtype'];
            isset($CampaignFeedType['bid']) && $data['bid'] = $CampaignFeedType['bid'];
            isset($CampaignFeedType['ocpc']) && $data['ocpc'] = $CampaignFeedType['ocpc'];
            isset($CampaignFeedType['inheritAscriptionType']) && $data['inheritAscriptionType'] = $CampaignFeedType['inheritAscriptionType'];
            isset($CampaignFeedType['inheritUserids']) && $data['inheritUserids'] = $CampaignFeedType['inheritUserids'];
            isset($CampaignFeedType['inheritCampaignInfos']) && $data['inheritCampaignInfos'] = $CampaignFeedType['inheritCampaignInfos'];
            isset($CampaignFeedType['bmcUserId']) && $data['bmcUserId'] = $CampaignFeedType['bmcUserId'];
            isset($CampaignFeedType['catalogId']) && $data['catalogId'] = $CampaignFeedType['catalogId'];
            isset($CampaignFeedType['catalogSource']) && $data['catalogSource'] = $CampaignFeedType['catalogSource'];
            isset($CampaignFeedType['miniProgramType']) && $data['miniProgramType'] = $CampaignFeedType['miniProgramType'];
            isset($CampaignFeedType['productType']) && $data['productType'] = $CampaignFeedType['productType'];
            isset($CampaignFeedType['projectFeedId']) && $data['projectFeedId'] = $CampaignFeedType['projectFeedId'];
            isset($CampaignFeedType['useLiftBudget']) && $data['useLiftBudget'] = $CampaignFeedType['useLiftBudget'];
            isset($CampaignFeedType['liftBudget']) && $data['liftBudget'] = $CampaignFeedType['liftBudget'];
            isset($CampaignFeedType['deliveryType']) && $data['deliveryType'] = $CampaignFeedType['deliveryType'];
            isset($CampaignFeedType['appSubType']) && $data['appSubType'] = $CampaignFeedType['appSubType'];
            isset($CampaignFeedType['bidMode']) && $data['bidMode'] = $CampaignFeedType['bidMode'];
            isset($CampaignFeedType['saleType']) && $data['saleType'] = $CampaignFeedType['saleType'];
            isset($CampaignFeedType['liftBudgetSchedule']) && $data['liftBudgetSchedule'] = $CampaignFeedType['liftBudgetSchedule'];
            $data2[$k] = $data;
        }
        $user_payload['body'] = [
            'campaignFeedTypes' => $data2,

        ];
        $jsonData = json_encode($user_payload);
        $url = 'https://api.baidu.com/json/feed/v1/CampaignFeedService/addCampaignFeed';
        $addresponse = getData($url, $jsonData);
        $addresponse = json_decode($addresponse, true);
        //var_dump($addresponse);
        if ($addresponse['header']['desc'] != 'success') {
            Log::channel('feed')->info('账户：' . $userName . ' 计划复制失败,crontab/feed.php,失败原因: ' . json_encode($addresponse));
        }
        //批量新建计划成功之后，批量新建单元
        if (is_array($addresponse['body']['data']) && count($addresponse['body']['data']) > 0) {
            //要复制的计划 id 跟新建出来的计划 id 对应关系
            $feed_new_id_ids = [];
            foreach ($addresponse['body']['data'] as $k => $CampaignFeedType) {
                if (isset($feed_name_ids[$CampaignFeedType['campaignFeedName']])) {
                    //$feed_new_id_ids[$CampaignFeedType['campaignFeedId']]=$feed_name_ids[$CampaignFeedType['campaignFeedName']];
                    $feed_new_id_ids[$feed_name_ids[$CampaignFeedType['campaignFeedName']]] = $CampaignFeedType['campaignFeedId'];
                }
            }
            //echo '计划id 数组'.PHP_EOL;
            //var_dump($feed_new_id_ids);
            //新建单元
            require app_path() . '/crontab/adgroupFeedType1.php';
        } else {
            echo '账户: ' . $userName . '新建计划失败，请排查原因' . json_encode($addresponse['header']);
        }
    }


} else
    var_dump($userName . '账户计划查询失败');
