<?php

namespace app\controller;

use app\model\Project;
use DateTime;
use support\Log;
use support\Redis;
use support\Request;
use support\Db;

class TestController
{
    protected $userName;

    public function __construct()
    {
        $this->userName = Request()->get('userName', 'J-故乡的云');
    }

    function a()
    {
        echo 1;
        //require app_path() . '/crontab/XinxiliuSubuser.php';

    }

    //信息流整体账户报告 https://dev2.baidu.com/content?sceneType=0&pageId=104806&nodeId=1564&subhead=
    public function index(Request $request)
    {
        $columns = ["date", "userId", "userName", "impression", "click", "cost", "ctr", "cpc", "cpm", "phoneButtonClicks", 'feedOCPCConversionsDetail3', 'ctFeedOCPCConversionsDetail3', 'phoneDialUpConversions', 'aggrFormClickSuccess', 'ctAggrFormClickSuccess', 'weiXinCopyConversions', 'ctWeiXinCopyConversions', 'advisoryClueCount', 'ctAdvisoryClueCount', 'weixinFollowSuccessConversions', 'ctWeixinFollowSuccessConversions', 'validConsult', 'ctValidConsult', 'weixinAppInvokeUv', 'ctWeixinAppInvokeUv'];

        // 设置循环开始日期为当月的第一天
        $startDay = new DateTime("first day of this month");
        //月初第一天
        $firstdayDate = $startDay->format('Y-m-d');
        $reportData = $this->report($this->userName, 2172649, $firstdayDate, $columns);
        $highlight = $request->get('highlight', '');
        if (!$highlight)
            return raw_view('index/json');
        else
            return json(['code' => 0, 'msg' => $reportData]);
    }





    //单元报告 https://dev2.baidu.com/content?sceneType=0&pageId=102479&nodeId=700&subhead=
    public function unit(Request $request)
    {
        $columns = ["date", "userId", "userName", "adGroupNameStatus", "impression", "click", "cost", "ctr", "cpc", "ocpcTransType", "ocpcTargetTrans", "ocpcTargetTransCPC", "ocpcTargetTransRatio", "campaignNameStatus", "adGroupNameStatus", "feedFlowTypeEnum", "adGroupStatus", "adGroupName", "campaignId", "adGroupId", "campaignStatus", "campaignName", "device", "feedSubjectEnum", "bsType"];

        $reportData = $this->report($this->userName, 2330652, date('Y-m-d', strtotime('-30 day')), $columns);
        $highlight = $request->get('highlight', '');
        if (!$highlight)
            return raw_view('index/json');
        else
            return json(['code' => 0, 'msg' => $reportData]);

    }

    //定向分析报告 意图词 https://dev2.baidu.com/content?sceneType=0&pageId=103894&nodeId=1064&subhead=
    public function word(Request $request)
    {
        $columns = ["date", "userId", "userName", "adGroupNameStatus", "impression", "click", "cost", "ctr", "cpc", "campaignNameStatus", "adGroupNameStatus", "adGroupStatus", "adGroupName", "campaignId", "adGroupId", "campaignStatus", "campaignName", "device", "feedSubjectEnum", "bsType", "showWord", "feedWord"];
        $reportData = $this->report($this->userName, 2532512, date('Y-m-d', strtotime('-30 day')), $columns);
        $highlight = $request->get('highlight', '');
        if (!$highlight)
            return raw_view('index/json');
        else
            return json(['code' => 0, 'msg' => $reportData]);

    }

    //定向分析报告 自动定向 https://dev2.baidu.com/content?sceneType=0&pageId=103894&nodeId=1064&subhead=
    public function auto(Request $request)
    {
        $columns = ["date", "userId", "userName", "adGroupNameStatus", "impression", "click", "cost", "ctr", "cpc", "campaignNameStatus", "adGroupNameStatus", "adGroupStatus", "adGroupName", "campaignId", "adGroupId", "campaignStatus", "campaignName"];
        $reportData = $this->report($this->userName, 9718402, date('Y-m-d', strtotime('-30 day')), $columns);
        $highlight = $request->get('highlight', '');
        if (!$highlight)
            return raw_view('index/json');
        else
            return json(['code' => 0, 'msg' => $reportData]);

    }

    //创意报告 https://dev2.baidu.com/content?sceneType=0&pageId=102481&nodeId=709&subhead=
    function product(Request $request)
    {
        $columns = ["date", "userId", "userName", "ideaInfo", "impression", "click", "cost", "ctr", "cpc", "completePlayCount", "completePlayRatio", "playCount1", "playCount2", "playCount3", "playCount4", "avgPlayTime", "completePlayCost", "ocpcTransType", "ocpcTargetTrans", "ocpcTargetTransCPC", "ocpcTargetTransRatio"];
        $reportData = $this->report($this->userName, 2094816, date('Y-m-d', strtotime('-30 day')), $columns);
        $highlight = $request->get('highlight', '');
        if (!$highlight)
            return raw_view('index/json');
        else
            return json(['code' => 0, 'msg' => $reportData]);
    }

    //视频报告 https://dev2.baidu.com/content?sceneType=0&pageId=102657&nodeId=727&subhead=
    function video(Request $request)
    {
        $columns = ["date", "userId", "userName", "ideaId", "videoId", "videoMD5", "videoName", "videoNameStatus", "videoInfo", "adGroupStatus", "adGroupNameStatus", "adGroupName", "campaignId", "adGroupId", "campaignNameStatus", "campaignStatus", "bsType", "campaignName", "device", "feedSubjectEnum", "impression", "click", "cost", "ctr", "cpc", "completePlayCount", "completePlayRatio", "playCount1", "playCount2", "playCount3", "playCount4", "avgPlayTime", "completePlayCost"];
        $reportData = $this->report($this->userName, 114718, date('Y-m-d', strtotime('-30 day')), $columns);
        $highlight = $request->get('highlight', '');
        if (!$highlight)
            return raw_view('index/json');
        else
            return json(['code' => 0, 'msg' => $reportData]);
    }

    //图片报告https://dev2.baidu.com/content?sceneType=0&pageId=104477&nodeId=1360&subhead=
    function picture(Request $request)
    {
        $columns = ["date", "userId", "userName", "imageUrl", "imageId", "campaignNameStatus", "adGroupNameStatus", "impression", "click", "cost", "ctr", "cpc", "ideaNameStatus"];
        $reportData = $this->report($this->userName, 2094817, date('Y-m-d', strtotime('-30 day')), $columns);
        $highlight = $request->get('highlight', '');
        if (!$highlight)
            return raw_view('index/json');
        else
            return json(['code' => 0, 'msg' => $reportData]);
    }

    //信息流落地页报告https://dev2.baidu.com/content?sceneType=0&pageId=100729&nodeId=593&subhead=
    function floor(Request $request)
    {
        $columns = ["date", "userId", "userName", "landingPageUrl", "campaignId", "campaignNameStatus", "adGroupNameStatus", "adGroupStatus", "click", "cost", "cpc"];
        $reportData = $this->report($this->userName, 6098145, date('Y-m-d', strtotime('-30 day')), $columns);
        $highlight = $request->get('highlight', '');
        if (!$highlight)
            return raw_view('index/json');
        else
            return json(['code' => 0, 'msg' => $reportData]);
    }

    //信息流访客明细报告 https://dev2.baidu.com/content?sceneType=0&pageId=100730&nodeId=596&subhead=
    function visitor(Request $request)
    {
        $columns = ["date", "userId", "userName", "cityName", "landingPageUrlId", "landingPageUrl", "ip", "landingPageDurationSec"];
        $reportData = $this->report($this->userName, 6759418, date('Y-m-d', strtotime('-14 day')), $columns);
        $highlight = $request->get('highlight', '');
        if (!$highlight)
            return raw_view('index/json');
        else
            return json(['code' => 0, 'msg' => $reportData]);
    }

    //获取视频内容见解数据 https://dev2.baidu.com/content?sceneType=0&pageId=102637&nodeId=711&subhead=
    function videolabel(Request $request)
    {
        $videoId = $request->get('videoId', '');
        $xinxiliu_refreshToken = DB::table('baidu_xinxiliu_refreshToken')->where('userId', 56325868)->first();
        $jsonData = json_encode(array(
            'header' => array(
                "userName" => $this->userName,
                "accessToken" => $xinxiliu_refreshToken->accessToken,
                'action' => 'API-PYTHON'
            ),
            'body' => array(
                'videoIds' => array($videoId),
            )
        ));
        $reportData = getVideoLabelData($jsonData);
        $highlight = $request->get('highlight', '');
        if (!$highlight)
            return raw_view('index/json');
        else
            return json(['code' => 0, 'msg' => $reportData]);
    }

    function report($userName, $reportType, $day, $columns)
    {
        $xinxiliu_refreshToken = DB::table('baidu_xinxiliu_refreshToken')->where('userId', 51992047)->first();
        $user_payload = array(
            "header" => array(
                "userName" => $userName,
                "accessToken" => $xinxiliu_refreshToken->accessToken,
                "action" => "API-PYTHON"
            ),
        );
        $user_payload['body'] = [
            "reportType" => $reportType,
            "startDate" => $day,
            //"endDate" =>'2024-06-14',
            "endDate" => date('Y-m-d'),
            "timeUnit" => "DAY",
            "columns" => $columns,
            "sorts" => [],
            "filters" => [],
            "startRow" => 0,
            "rowCount" => 2000,
            "needSum" => true
        ];
        $jsonData = json_encode($user_payload);
        $reportData = getReportData($jsonData);
        return $reportData;
    }

}