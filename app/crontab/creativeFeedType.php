<?php
//信息流计划 创意  复制 删除
namespace app\crontab;

use support\Db;

// 记录开始时间
$startTime = microtime(true);
$userName = 'RQYZ-旭日辉春';

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
    echo '列出计划信息' . PHP_EOL;
    var_dump($feedData['body']['data']);
    //计划Id 数组
    $campaignFeedIds = array_column($feedData['body']['data'], 'campaignFeedId');
    //查询单元
    $adgroupFeedData = getAdgroupFeed($user_payload, $campaignFeedIds);
    //如果单元数据查询成功
    if ($adgroupFeedData['header']['desc'] == 'success') {
        echo '列出单元信息' . PHP_EOL;
        var_dump($adgroupFeedData['body']['data']);
        //单元Id 数组
        $adgroupFeedIds = array_column($adgroupFeedData['body']['data'], 'adgroupFeedId');
        //查询创意
        $adgroupFeedData = getCreativeFeed($user_payload, $campaignFeedIds);
        echo '列出创意信息' . PHP_EOL;
        var_dump($adgroupFeedData);
        //如果单元数据查询成功
        if ($adgroupFeedData['header']['desc'] == 'success') {
            $data = [];
            foreach ($adgroupFeedData['body']['data'] as $k => $adgroupFeed) {
                $adgroupFeed['material'] = json_decode($adgroupFeed['material'], true);
                $data[$k] = [
                    'adgroupFeedId' => $adgroupFeed['adgroupFeedId'],
                    'adgroupFeedId' => 11032518881,
                    'materialstyle' => $adgroupFeed['materialstyle'],
                    'creativeFeedName' => $adgroupFeed['creativeFeedName'] . mt_rand(1, 100),
                    'pause' => false,

                    'ideaType' => $adgroupFeed['ideaType'],
                    'commentnum' => $adgroupFeed['commentnum'],
                    'readnum' => $adgroupFeed['readnum'],
                    'playnum' => $adgroupFeed['playnum'],
                    'progFlag' => $adgroupFeed['progFlag'],
                ];
                isset($adgroupFeed['material']['title']) && $data[$k]['material']['title'] = $adgroupFeed['material']['title'];
                isset($adgroupFeed['material']['brand']) && $data[$k]['material']['brand'] = $adgroupFeed['material']['brand'];
                isset($adgroupFeed['material']['subtitle']) && $data[$k]['material']['subtitle'] = $adgroupFeed['material']['subtitle'];
                isset($adgroupFeed['material']['userPortrait']) && $data[$k]['material']['userPortrait'] = $adgroupFeed['material']['userPortrait'];
                isset($adgroupFeed['material']['pictures']) && $data[$k]['material']['pictures'] = $adgroupFeed['material']['pictures'];
                isset($adgroupFeed['material']['pictureWidth']) && $data[$k]['material']['pictureWidth'] = $adgroupFeed['material']['pictureWidth'];
                isset($adgroupFeed['material']['pictureHeight']) && $data[$k]['material']['pictureHeight'] = $adgroupFeed['material']['pictureHeight'];
                isset($adgroupFeed['material']['url']) && $data[$k]['material']['url'] = $adgroupFeed['material']['url'];
                isset($adgroupFeed['material']['newDownloadUrl']) && $data[$k]['material']['newDownloadUrl'] = $adgroupFeed['material']['newDownloadUrl'];
                isset($adgroupFeed['material']['videoid']) && $data[$k]['material']['videoid'] = $adgroupFeed['material']['videoid'];
                isset($adgroupFeed['material']['posterid']) && $data[$k]['material']['posterid'] = $adgroupFeed['material']['posterid'];
                isset($adgroupFeed['material']['poster']) && $data[$k]['material']['poster'] = $adgroupFeed['material']['poster'];
                isset($adgroupFeed['material']['horizontalCoverId']) && $data[$k]['material']['horizontalCoverId'] = $adgroupFeed['material']['horizontalCoverId'];
                isset($adgroupFeed['material']['horizontalCover']) && $data[$k]['material']['horizontalCover'] = $adgroupFeed['material']['horizontalCover'];
                isset($adgroupFeed['material']['videoText1']) && $data[$k]['material']['videoText1'] = $adgroupFeed['material']['videoText1'];
                isset($adgroupFeed['material']['videoText2']) && $data[$k]['material']['videoText2'] = $adgroupFeed['material']['videoText2'];
                isset($adgroupFeed['material']['naUrl']) && $data[$k]['material']['naUrl'] = $adgroupFeed['material']['naUrl'];
                isset($adgroupFeed['material']['monitorUrl']) && $data[$k]['material']['monitorUrl'] = $adgroupFeed['material']['monitorUrl'];
                isset($adgroupFeed['material']['exposureUrl']) && $data[$k]['material']['exposureUrl'] = $adgroupFeed['material']['exposureUrl'];
                isset($adgroupFeed['material']['monitorUrlType']) && $data[$k]['material']['monitorUrlType'] = $adgroupFeed['material']['monitorUrlType'];
                isset($adgroupFeed['material']['exposureUrlType']) && $data[$k]['material']['exposureUrlType'] = $adgroupFeed['material']['exposureUrlType'];
                isset($adgroupFeed['material']['ulkUrl']) && $data[$k]['material']['ulkUrl'] = $adgroupFeed['material']['ulkUrl'];
                isset($adgroupFeed['material']['ulkScheme']) && $data[$k]['material']['ulkScheme'] = $adgroupFeed['material']['ulkScheme'];
                isset($adgroupFeed['material']['ideaPluginGroup']) && $data[$k]['material']['ideaPluginGroup'] = $adgroupFeed['material']['ideaPluginGroup'];
                isset($adgroupFeed['material']['pluginIds']) && $data[$k]['material']['pluginIds'] = $adgroupFeed['material']['pluginIds'];
                isset($adgroupFeed['material']['elements']['titles']) && $data[$k]['material']['elements']['titles'] = $adgroupFeed['material']['elements']['titles'];
                isset($adgroupFeed['material']['elements']['pictureSingle']) && $data[$k]['material']['elements']['pictureSingle'] = $adgroupFeed['material']['elements']['pictureSingle'];
                isset($adgroupFeed['material']['elements']['pictureLarge']) && $data[$k]['material']['elements']['pictureLarge'] = $adgroupFeed['material']['elements']['pictureLarge'];
                isset($adgroupFeed['material']['elements']['picture3Fixed']) && $data[$k]['material']['elements']['picture3Fixed'] = $adgroupFeed['material']['elements']['picture3Fixed'];
                isset($adgroupFeed['material']['elements']['videoHorizontal']['videoId']) && $data[$k]['material']['elements']['videoHorizontal']['videoId'] = $adgroupFeed['material']['elements']['videoHorizontal']['videoId'];
                isset($adgroupFeed['material']['elements']['videoHorizontal']['videoUrl']) && $data[$k]['material']['elements']['videoHorizontal']['videoUrl'] = $adgroupFeed['material']['elements']['videoHorizontal']['videoUrl'];
                if (isset($adgroupFeed['material']['elements']['videoHorizontal']) && count($adgroupFeed['material']['elements']['videoHorizontal']) > 0) {
                    foreach ($adgroupFeed['material']['elements']['videoHorizontal'] as $_k => $videoHorizontal) {
                        $data[$k]['material']['elements']['videoHorizontal'][$_k]['videoUrl'] = $videoHorizontal['videoUrl'];
                        $data[$k]['material']['elements']['videoHorizontal'][$_k]['videoId'] = $videoHorizontal['videoId'];
                        $data[$k]['material']['elements']['videoHorizontal'][$_k]['poster'] = $videoHorizontal['poster'];
                    }
                }

                if (isset($adgroupFeed['material']['elements']['videoVertical']) && count($adgroupFeed['material']['elements']['videoVertical']) > 0) {
                    foreach ($adgroupFeed['material']['elements']['videoVertical'] as $_k => $videoVertical) {
                        $data[$k]['material']['elements']['videoVertical'][$_k]['videoUrl'] = $videoVertical['videoUrl'];
                        $data[$k]['material']['elements']['videoVertical'][$_k]['videoId'] = $videoVertical['videoId'];
                        $data[$k]['material']['elements']['videoVertical'][$_k]['poster'] = $videoVertical['poster'];

                    }
                }
                $data[$k]['material'] = json_encode($data[$k]['material']);

            }
            var_dump($data);
            //创建创意
            $user_payload['body'] = [
                'creativeFeedTypes' => $data,
            ];

            $jsonData = json_encode($user_payload);
            $url = 'https://api.baidu.com/json/feed/v1/CreativeFeedService/addCreativeFeed';
            $response = getData($url, $jsonData);
            $response = json_decode($response, true);
            var_dump($response);
        }

    }

}

function getAdgroupFeed($user_payload, $ids)
{

    $adgroupFeedFields = [
        'adgroupFeedId' => '推广单元ID',
        'campaignFeedId' => '推广计划ID',
        'adgroupFeedName' => '推广单元名称',
        'pause' => '暂停/启用推广单元',
        'status' => '推广单元状态',
        'bid' => '出价',
        'ftypes' => '投放范围',
        'bidtype' => '优化目标和付费模式',
        'ocpc' => 'oCPC信息',
        'atpFeedId' => '定向包ID',
        'addtime' => '添加时间',
        'modtime' => '修改时间',
        'deliveryType' => '投放场景',
        'unefficientAdgroup' => '低效单元',
        'productSetId' => '商品组ID（仅商品推广）',
        'ftypeSelection' => '是否使用计划流量',
        'bidSource' => '是否使用计划出价',
        'unitOcpxStatus' => '单元学习状态',
        'atpName' => '定向包名称',
        'region' => '地域（省市区县）',
        'bizArea' => '预置商圈',
        'customArea' => '自定义商圈	',
        'place' => '场所',
        'useractiontype' => '用户到访类型',
        'age' => '年龄',
        'customAge' => '自定义年龄',
        'sex' => '性别',
        'lifeStage' => '人生阶段',
        'education' => '学历',
        'newInterests' => '新兴趣（兴趣2.0）',
        'keywords' => '意图词',
        'keywordsExtend' => '意图词用户行为',
        'crowd' => '人群包（定向人群）',
        'excludeCrowd' => '人群包（排除人群）',
        'excludeTrans' => '排除已转化人群',
        'excludeTransFilterTime' => '排除同主体已转化人群-时间过滤',
        'device' => '操作系统',
        'iosVersion' => 'iOS系统版本',
        'androidVersion' => 'Android系统版本',
        'androidBrands' => '手机品牌',
        'mobilePhonePrice' => '手机价格',
        'phonePrice' => '手机价格',
        'telecom' => '运营商',
        'app' => 'APP行为',
        'net' => '网络',
        'mediaPackage' => '百青藤精选媒体包',
        'mediaCategoriesBindType' => '百青藤媒体分类使用方式',
        'mediaCategories' => '百青藤媒体分类',
        'mediaidsBindType' => '百青藤自定义媒体包使用方式',
        'customMediaPackage' => '百青藤自定义媒体包',
        'deeplinkOnly' => '是否仅投放至允许调起的媒体',
        'articleType' => '文章分类',
        'eshopcrowds' => '已安装APP',
        'eldscrowds' => '电商推荐人群',
        'tradecrowds' => '电商推荐人群',
        'naUrl' => '调起URL',
        'url' => '推广URL',
        'premium' => '商品通投定向设置',
        'useKyp' => '商品智能买词定向定向设置',
        'rtaRedirect' => '商品RTA重定向设置',
        'paKeywords' => '商品单元意图词定向设置',
        'paCrowd' => '商品人群定向设置',
        'autoRegion' => '智能地域',
        'productIds' => '商品ID/产品ID',
        'userType' => '人群常住地类型',
    ];
    $adgroupFeedFields = array_keys($adgroupFeedFields);
    $user_payload['body'] = [
        'adgroupFeedFields' => $adgroupFeedFields,
        'ids' => $ids,
        'idType' => 1
    ];
    $jsonData = json_encode($user_payload);
    $url = 'https://api.baidu.com/json/feed/v1/AdgroupFeedService/getAdgroupFeed';
    $response = getData($url, $jsonData);
    $response = json_decode($response, true);
    return $response;

}
if (!function_exists('getCreativeFeed')) {
    function getCreativeFeed($user_payload, $ids)
    {
        $creativeFeedFields = [
            'creativeFeedId' => '创意ID',
            'adgroupFeedId' => '推广单元ID',
            'materialstyle' => '样式',
            'creativeFeedName' => '创意名称',
            'pause' => '暂停/启用创意',
            'material' => '物料内容',
            'status' => '创意状态',
            'refusereason' => '拒绝原因',
            'expmask' => '产品标示位',
            'changeorder' => '三图换顺序开关',
            'commentnum' => '显示评论数开关',
            'readnum' => '显示阅读数开关',
            'playnum' => '显示播放数开关',
            'ideaType' => '创意类型',
            'showMt' => '程序化创意展示样式',
            'addtime' => '创意添加时间',
            'progFlag' => '程序化创意工具标识',
            'approvemsgnew' => '格式化的拒绝理由',
            'auditTimeModel' => '产生类型',
            'template' => '商品目录创意参数',
            'huitus' => '商品目录创意绑定慧图模板ID',
        ];
        $creativeFeedFields = array_keys($creativeFeedFields);
        $user_payload['body'] = [
            'creativeFeedFields' => $creativeFeedFields,
            'ids' => $ids,
            'idType' => 1
        ];
        $jsonData = json_encode($user_payload);
        $url = 'https://api.baidu.com/json/feed/v1/CreativeFeedService/getCreativeFeed';
        $response = getData($url, $jsonData);
        $response = json_decode($response, true);
        return $response;
    }
}