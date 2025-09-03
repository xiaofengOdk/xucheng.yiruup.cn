<?php

namespace app\controller;

use support\Log;
use support\Request;
use support\Db;

class XinController
{
    protected $userName;

    public function __construct()
    {
        $this->userName = Request()->get('userName', 'RQYZ-祝贺发财');
    }
    //查询计划
    public function index(Request $request)
    {
        $xinxiliu_subuser=DB::table('baidu_xinxiliu_subuser')->where('userName', $this->userName)->first();
        $xinxiliu_refreshToken = DB::table('baidu_xinxiliu_refreshToken')->where('userId', $xinxiliu_subuser->masterUid)->first();
        var_dump($xinxiliu_subuser);
        $user_payload = array(
            "header" => array(
                "userName" => $this->userName,
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
        $campaignData=array_keys($campaignData);
        $user_payload['body']=[
            'campaignFeedFields' => $campaignData,
            'campaignFeedIds'=>[],
        ];
        $jsonData = json_encode($user_payload);
        $reportData = getCampaignFeed($jsonData);
        $campaignFeedIds = array_column($reportData['body']['data'], 'campaignFeedId');
        $highlight = $request->get('highlight', '');
        if (!$highlight)
            return raw_view('index/json');
        else
            return json(['code' => 0, 'msg' => $reportData]);

    }
    //查询单元https://api.baidu.com/json/feed/v1/AdgroupFeedService/getAdgroupFeed
    public function getAdgroupFeed(Request $request)
    {
        $xinxiliu_subuser=DB::table('baidu_xinxiliu_subuser')->where('userName', $this->userName)->first();
        $xinxiliu_refreshToken = DB::table('baidu_xinxiliu_refreshToken')->where('userId', $xinxiliu_subuser->masterUid)->first();

        $user_payload = array(
            "header" => array(
                "userName" => $this->userName,
                "accessToken" => $xinxiliu_refreshToken->accessToken,
                "action" => "API-PYTHON"
            ),
        );
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
            'region'=> '地域（省市区县）',
            'bizArea'=>'预置商圈',
            'customArea'=>'自定义商圈	',
            'place'=>'场所',
            'useractiontype'=>'用户到访类型',
            'age'=>'年龄',
            'customAge'=>'自定义年龄',
            'sex'=>'性别',
            'lifeStage'=>'人生阶段',
            'education'=>'学历',
            'newInterests'=>'新兴趣（兴趣2.0）',
            'keywords'=>'意图词',
            'keywordsExtend'=>'意图词用户行为',
            'crowd'=>'人群包（定向人群）',
            'excludeCrowd'=>'人群包（排除人群）',
            'excludeTrans'=>'排除已转化人群',
            'excludeTransFilterTime'=>'排除同主体已转化人群-时间过滤',
            'device'=>'操作系统',
            'iosVersion'=>'iOS系统版本',
            'androidVersion'=>'Android系统版本',
            'androidBrands'=>'手机品牌',
            'mobilePhonePrice'=>'手机价格',
            'phonePrice'=>'手机价格',
            'telecom'=>'运营商',
            'app'=>'APP行为',
            'net'=>'网络',
            'mediaPackage'=>'百青藤精选媒体包',
            'mediaCategoriesBindType'=>'百青藤媒体分类使用方式',
            'mediaCategories'=>'百青藤媒体分类',
            'mediaidsBindType'=>'百青藤自定义媒体包使用方式',
            'customMediaPackage'=>'百青藤自定义媒体包',
            'deeplinkOnly'=>'是否仅投放至允许调起的媒体',
            'articleType'=>'文章分类',
            'eshopcrowds'=>'已安装APP',
            'eldscrowds'=>'电商推荐人群',
            'tradecrowds'=>'电商推荐人群',
            'naUrl'=>'调起URL',
            'url'=>'推广URL',
            'premium'=>'商品通投定向设置',
            'useKyp'=>'商品智能买词定向定向设置',
            'rtaRedirect'=>'商品RTA重定向设置',
            'paKeywords'=>'商品单元意图词定向设置',
            'paCrowd'=>'商品人群定向设置',
            'autoRegion'=>'智能地域',
            'productIds'=>'商品ID/产品ID',
            'userType'=>'人群常住地类型',
        ];
        $adgroupFeedFields=array_keys($adgroupFeedFields);
        $user_payload['body']=[
            'adgroupFeedFields' => $adgroupFeedFields,
            'ids'=>[759175145,751413453,759175146,759175147,759175148,759175149,759175150,759175151,759175152,759175153,751411483,759175154],
            'idType'=>1
        ];
        $jsonData = json_encode($user_payload);
        $url='https://api.baidu.com/json/feed/v1/AdgroupFeedService/getAdgroupFeed';
        $response = getData($url, $jsonData);
        $response = json_decode($response, true);
        $highlight = $request->get('highlight', '');
        if (!$highlight)
            return raw_view('index/json');
        else
            return json(['code' => 0, 'msg' => $response]);

    }
    //查询创意https://api.baidu.com/json/feed/v1/CreativeFeedService/getCreativeFeed
    public function getCreativeFeed(Request $request)
    {
        $xinxiliu_subuser=DB::table('baidu_xinxiliu_subuser')->where('userName', $this->userName)->first();
        $xinxiliu_refreshToken = DB::table('baidu_xinxiliu_refreshToken')->where('userId', $xinxiliu_subuser->masterUid)->first();

        $user_payload = array(
            "header" => array(
                "userName" => $this->userName,
                "accessToken" => $xinxiliu_refreshToken->accessToken,
                "action" => "API-PYTHON"
            ),
        );
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
        $creativeFeedFields=array_keys($creativeFeedFields);

        $user_payload['body']=[
            'creativeFeedFields' => $creativeFeedFields,
            'ids'=>[759175145,751413453,759175146,759175147,759175148,759175149,759175150,759175151,759175152,759175153,751411483,759175154],
            'idType'=>1
        ];

        $jsonData = json_encode($user_payload);
        $url='https://api.baidu.com/json/feed/v1/CreativeFeedService/getCreativeFeed';
        $response = getData($url, $jsonData);
        $response = json_decode($response, true);
        $highlight = $request->get('highlight', '');
        if (!$highlight)
            return raw_view('index/json');
        else
            return json(['code' => 0, 'msg' => $response]);
    }
}