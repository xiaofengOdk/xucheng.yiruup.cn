<?php
//信息流计划 单元  复制 删除
namespace app\crontab;

use support\Db;

// 记录开始时间
$startTime = microtime(true);
$userName = 'JXF-升官发财';

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
    //查询单元
    $adgroupFeedData = getAdgroupFeed($user_payload, $campaignFeedIds);
    //如果单元数据查询成功
    if ($adgroupFeedData['header']['desc'] == 'success') {
        //新建单元
        if (is_array($adgroupFeedData['body']['data']) && count($adgroupFeedData['body']['data']) > 0) {
            foreach ($adgroupFeedData['body']['data'] as $adgroupFeed) {
                if ($adgroupFeed['campaignFeedId'] == 757261058) {
                    var_dump($adgroupFeed);
                    $data = [];
                    //推广计划ID
                    $data['campaignFeedId'] = $adgroupFeed['campaignFeedId'];
                    //推广单元名称
                    $data['adgroupFeedName'] = $adgroupFeed['adgroupFeedName'] . mt_rand(100, 1000);
                    //暂停/启用推广单元
                    $data['pause'] = false;
                    isset($adgroupFeed['audience']) && $data['audience'] = $adgroupFeed['audience'];
                    isset($adgroupFeed['bid']) && $data['bid'] = $adgroupFeed['bid'];
                    isset($adgroupFeed['ftypes']) && $data['ftypes'] = $adgroupFeed['ftypes'];
                    isset($adgroupFeed['bidtype']) && $data['bidtype'] = $adgroupFeed['bidtype'];

                    isset($adgroupFeed['atpFeedId']) && $data['atpFeedId'] = $adgroupFeed['atpFeedId'];
                    isset($adgroupFeed['deliveryType']) && $data['deliveryType'] = $adgroupFeed['deliveryType'];
                    isset($adgroupFeed['productSetId']) && $data['productSetId'] = $adgroupFeed['productSetId'];
                    isset($adgroupFeed['unitProducts']) && $data['unitProducts'] = $adgroupFeed['unitProducts'];
                    isset($adgroupFeed['ftypeSelection']) && $data['ftypeSelection'] = $adgroupFeed['ftypeSelection'];
                    isset($adgroupFeed['bidSource']) && $data['bidSource'] = $adgroupFeed['bidSource'];
                    isset($adgroupFeed['urlType']) && $data['urlType'] = $adgroupFeed['urlType'];
                    isset($adgroupFeed['miniProgram']) && $data['miniProgram'] = $adgroupFeed['miniProgram'];
                    isset($adgroupFeed['broadCastInfo']) && $data['broadCastInfo'] = $adgroupFeed['broadCastInfo'];
                    isset($adgroupFeed['url']) && $data['url'] = $adgroupFeed['url'];
                    isset($adgroupFeed['createAtp']) && $data['createAtp'] = $adgroupFeed['createAtp'];
                    isset($adgroupFeed['atpName']) && $data['atpName'] = $adgroupFeed['atpName'];
                    isset($adgroupFeed['atpDesc']) && $data['atpDesc'] = $adgroupFeed['atpDesc'];
                    isset($adgroupFeed['ocpc']) && $data['ocpc'] = $adgroupFeed['ocpc'];
                    //使用计划出价时，不需要传bid、bidtype以及oCPC中的出价相关字段，优先使用计划出价设置
                    //出价上移名单使用字段，名单外使用无效。
                    if ($adgroupFeed['bidSource'] == 2) {
                        unset($data['bid']);
                        unset($data['bidtype']);
                        unset($data['ocpc']['ocpcBid']);
                        unset($data['ocpc']['deepOcpcBid']);

                    }
                    $data2 = [];
                    //推广计划ID
                    $data2['campaignFeedId'] = $adgroupFeed['campaignFeedId'];
                    //推广单元名称
                    $data2['adgroupFeedName'] = $adgroupFeed['adgroupFeedName'] . mt_rand(100, 1000);
                    //暂停/启用推广单元
                    $data2['pause'] = false;
                    $data2['audience']['region'] = $adgroupFeed['audience']['region'];
                    isset($adgroupFeed['audience']['bizArea']) && $data2['audience']['bizArea'] = $adgroupFeed['audience']['bizArea'];

                    isset($adgroupFeed['audience']['customArea']) && $data2['audience']['customArea'] = $adgroupFeed['audience']['customArea'];

                    isset($adgroupFeed['audience']['place']) && $data2['audience']['place'] = $adgroupFeed['audience']['place'];

                    isset($adgroupFeed['audience']['useractiontype']) && $data2['audience']['useractiontype'] = $adgroupFeed['audience']['useractiontype'];

                    isset($adgroupFeed['audience']['age']) && $data2['audience']['age'] = $adgroupFeed['audience']['age'];

                    isset($adgroupFeed['audience']['customAge']) && $data2['audience']['customAge'] = $adgroupFeed['audience']['customAge'];

                    isset($adgroupFeed['audience']['sex']) && $data2['audience']['sex'] = $adgroupFeed['audience']['sex'];

                    isset($adgroupFeed['audience']['lifeStage']) && $data2['audience']['lifeStage'] = $adgroupFeed['audience']['lifeStage'];
                    isset($adgroupFeed['audience']['education']) && $data2['audience']['education'] = $adgroupFeed['audience']['education'];
                    isset($adgroupFeed['audience']['newInterests']) && $data2['audience']['newInterests'] = $adgroupFeed['audience']['newInterests'];
                    isset($adgroupFeed['audience']['keywords']) && $data2['audience']['keywords'] = $adgroupFeed['audience']['keywords'];
                    isset($adgroupFeed['audience']['keywordsExtend']) && $data2['audience']['keywordsExtend'] = $adgroupFeed['audience']['keywordsExtend'];
                    isset($adgroupFeed['audience']['crowd']) && $data2['audience']['crowd'] = $adgroupFeed['audience']['crowd'];
                    isset($adgroupFeed['audience']['excludeCrowd']) && $data2['audience']['excludeCrowd'] = $adgroupFeed['audience']['excludeCrowd'];
                    isset($adgroupFeed['audience']['excludeTrans']) && $data2['audience']['excludeTrans'] = $adgroupFeed['audience']['excludeTrans'];

                    isset($adgroupFeed['audience']['excludeTransFilterTime']) && $data2['audience']['excludeTransFilterTime'] = $adgroupFeed['audience']['excludeTransFilterTime'];

                    isset($adgroupFeed['audience']['device']) && $data2['audience']['device'] = $adgroupFeed['audience']['device'];

                    isset($adgroupFeed['audience']['iosVersion']) && $data2['audience']['iosVersion'] = $adgroupFeed['audience']['iosVersion'];

                    isset($adgroupFeed['audience']['androidVersion']) && $data2['audience']['androidVersion'] = $adgroupFeed['audience']['androidVersion'];

                    isset($adgroupFeed['audience']['androidBrands']) && $data2['audience']['androidBrands'] = $adgroupFeed['audience']['androidBrands'];

                    isset($adgroupFeed['audience']['mobilePhonePrice']) && $data2['audience']['mobilePhonePrice'] = $adgroupFeed['audience']['mobilePhonePrice'];

                    isset($adgroupFeed['audience']['phonePrice']) && $data2['audience']['phonePrice'] = $adgroupFeed['audience']['phonePrice'];

                    isset($adgroupFeed['audience']['telecom']) && $data2['audience']['telecom'] = $adgroupFeed['audience']['telecom'];

                    isset($adgroupFeed['audience']['app']) && $data2['audience']['app'] = $adgroupFeed['audience']['app'];

                    isset($adgroupFeed['audience']['net']) && $data2['audience']['net'] = $adgroupFeed['audience']['net'];

                    isset($adgroupFeed['audience']['mediaPackage']) && $data2['audience']['mediaPackage'] = $adgroupFeed['audience']['mediaPackage'];

                    isset($adgroupFeed['audience']['mediaCategoriesBindType']) && $data2['audience']['mediaCategoriesBindType'] = $adgroupFeed['audience']['mediaCategoriesBindType'];

                    isset($adgroupFeed['audience']['mediaCategories']) && $data2['audience']['mediaCategories'] = $adgroupFeed['audience']['mediaCategories'];

                    isset($adgroupFeed['audience']['mediaidsBindType']) && $data2['audience']['mediaidsBindType'] = $adgroupFeed['audience']['mediaidsBindType'];

                    isset($adgroupFeed['audience']['customMediaPackage']) && $data2['audience']['customMediaPackage'] = $adgroupFeed['audience']['customMediaPackage'];

                    isset($adgroupFeed['audience']['deeplinkOnly']) && $data2['audience']['deeplinkOnly'] = $adgroupFeed['audience']['deeplinkOnly'];

                    isset($adgroupFeed['audience']['articleType']) && $data2['audience']['articleType'] = $adgroupFeed['audience']['articleType'];

                    isset($adgroupFeed['audience']['eshopcrowds']) && $data2['audience']['eshopcrowds'] = $adgroupFeed['audience']['eshopcrowds'];

                    isset($adgroupFeed['audience']['eldscrowds']) && $data2['audience']['eldscrowds'] = $adgroupFeed['audience']['eldscrowds'];

                    isset($adgroupFeed['audience']['tradecrowds']) && $data2['audience']['tradecrowds'] = $adgroupFeed['audience']['tradecrowds'];

                    isset($adgroupFeed['audience']['naUrl']) && $data2['audience']['naUrl'] = $adgroupFeed['audience']['naUrl'];

                    isset($adgroupFeed['audience']['url']) && $data2['audience']['url'] = $adgroupFeed['audience']['url'];

                    isset($adgroupFeed['audience']['premium']) && $data2['audience']['premium'] = $adgroupFeed['audience']['premium'];

                    isset($adgroupFeed['audience']['useKyp']) && $data2['audience']['useKyp'] = $adgroupFeed['audience']['useKyp'];

                    //isset($adgroupFeed['audience']['rtaRedirect'])&&$data2['audience']['rtaRedirect'] = $adgroupFeed['audience']['rtaRedirect'];

                    //isset($adgroupFeed['audience']['paKeywords'])&&$data2['audience']['paKeywords'] = $adgroupFeed['audience']['paKeywords'];

                    // isset($adgroupFeed['audience']['paCrowd'])&&$data2['audience']['paCrowd'] = $adgroupFeed['audience']['paCrowd'];

                    // isset($adgroupFeed['audience']['autoRegion'])&&$data2['audience']['autoRegion'] = $adgroupFeed['audience']['autoRegion'];

                    //isset($adgroupFeed['audience']['productIds'])&&$data2['audience']['productIds'] = $adgroupFeed['audience']['productIds'];

                    isset($adgroupFeed['audience']['userType']) && $data2['audience']['userType'] = $adgroupFeed['audience']['userType'];


                    $data2['ftypes'] = $adgroupFeed['ftypes'];
                    $data2['bidtype'] = $adgroupFeed['bidtype'];
                    $data2['ocpc'] = $adgroupFeed['ocpc'];
                    $data2['productSetId'] = '';
                    $data1[0] = $data2;
                    $user_payload['body'] = [
                        'adgroupFeedTypes' => $data1,
                    ];

                    $jsonData = json_encode($user_payload);
                    $url = 'https://api.baidu.com/json/feed/v1/AdgroupFeedService/addAdgroupFeed';
                    $response = getData($url, $jsonData);
                    $response = json_decode($response, true);
                    var_dump($response);
                }

            }
        }
    }

} else
    var_dump($userName . '账户单元查询失败');
////查询单元https://api.baidu.com/json/feed/v1/AdgroupFeedService/getAdgroupFeed
if (!function_exists('getAdgroupFeed')) {
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
}
