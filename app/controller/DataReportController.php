<?php

namespace app\controller;

use DateTime;
use support\Request;
use support\Db;

class DataReportController
{
    protected $userName;

    public function __construct()
    {
        $this->userName = Request()->get('userName', 'A-记其事');
    }
    //当天加粉的实时报表
    public function day(Request $request){
        if ($request->method() == 'POST') {
            return json(['code' => 0, 'msg' => $request->post()]);
        } else {
            // 如果会话中没有 CSRF Token，则生成一个
            if (!$request->session()->has('csrf_token')) {
                $request->session()->put('csrf_token', md5(uniqid()));
            }

            $reportData = [];
            $reportData['csrf_token'] = $request->session()->get('csrf_token');

            $date = $request->get('date', date('Y-m-d'));
            $projectid = intval(mcrypt_decode($request->get('project', '4226TI-4csKBv_qPfwJDaOUItH9hx3FfuENLqoINnkVIUFDf94aNWf_0VcpiApvipH12Q3FNtWKyGkY')));
            $projectFirst = Db::table('baidu_xinxiliu_project')->where('id', $projectid)->first();

            if ($projectFirst) {
                $reportData['curdate'] = $date;
                $reportData['projectName'] = $projectFirst->clientName;
                $project = Db::table('baidu_xinxiliu_project')->where('clientName', $projectFirst->clientName)->limit(200)->get()->toArray();
                $project_id_data = [];
                foreach ($project as $item) {
                    $project_id_data[$item->id] = $item;
                }
                $project_ids = array_column($project, 'id');
                //真实加粉数据
                $trueWeixinData = Db::table('baidu_xinxiliu_project_trueweixinfollow')
                    ->where('projectName', $projectFirst->clientName)
                    ->where('eventDate', '=', $date)
                    ->get()->toArray();
                $trueWeixin = [];
                foreach ($trueWeixinData as $v) {
                    $trueWeixin[$v->eventDate] = $v;
                }
                $data = Db::table('baidu_xinxiliu_project_reportdata')
                    ->where('eventDate', '=', $date)
                    ->whereIn('projectId', $project_ids)
                    ->orderBy('eventDate', 'DESC')
                    ->get()->toArray();
                $userIds= array_column($data, 'userId');
                $reportData1=Db::table('baidu_xinxiliu_reportdata')
                    ->where('eventDate', '=', $date)
                    ->whereIn('userId', $userIds)
                    ->orderBy('eventDate', 'DESC')
                    ->get()->toArray();
                $reportDataMap = [];
                foreach ($reportData1 as $v){
                    $reportDataMap[$v->userId]=$v;
                }
                $r = [];
                foreach ($data as $item) {
                    $r[$item->eventDate]['projectName'] = $item->projectName;
                    $r[$item->eventDate]['cost'] = isset($r[$item->eventDate]['cost']) ? $r[$item->eventDate]['cost'] : 0;
                    $r[$item->eventDate]['cost'] +=  $reportDataMap[$item->userId]->cost;

                    $r[$item->eventDate]['weiXinCopyConversions'] = isset($r[$item->eventDate]['weiXinCopyConversions']) ? $r[$item->eventDate]['weiXinCopyConversions'] : 0;
                    $r[$item->eventDate]['weiXinCopyConversions'] = $r[$item->eventDate]['weiXinCopyConversions'] + $item->weiXinCopyConversions;

                    $r[$item->eventDate]['monthWeiXinCopyConversions'] = isset($r[$item->eventDate]['monthWeiXinCopyConversions']) ? $r[$item->eventDate]['monthWeiXinCopyConversions'] : 0;
                    $r[$item->eventDate]['monthWeiXinCopyConversions'] = $r[$item->eventDate]['monthWeiXinCopyConversions'] + $item->monthWeiXinCopyConversions;

                    $r[$item->eventDate]['ctWeiXinCopyConversions'] = isset($r[$item->eventDate]['ctWeiXinCopyConversions']) ? $r[$item->eventDate]['ctWeiXinCopyConversions'] : 0;
                    $r[$item->eventDate]['ctWeiXinCopyConversions'] = $r[$item->eventDate]['ctWeiXinCopyConversions'] + $item->monthWeiXinCopyConversions;

                    $r[$item->eventDate]['weixinFollowSuccessConversions'] = isset($r[$item->eventDate]['weixinFollowSuccessConversions']) ? $r[$item->eventDate]['weixinFollowSuccessConversions'] : 0;
                    $r[$item->eventDate]['weixinFollowSuccessConversions'] = $r[$item->eventDate]['weixinFollowSuccessConversions'] + $item->weixinFollowSuccessConversions;

                    //日的消耗币/加粉成功价
                    $r[$item->eventDate]['cost_weixin'] = isset($r[$item->eventDate]['cost_weixin']) ? $r[$item->eventDate]['cost_weixin'] : 0;;
                    $r[$item->eventDate]['cost_weixin'] = $item->weixinFollowSuccessConversions == 0 ? 0 : $r[$item->eventDate]['cost_weixin'] + ($item->cost / $item->weixinFollowSuccessConversions);


                    $r[$item->eventDate]['monthWeixinFollowSuccessConversions'] = isset($r[$item->eventDate]['monthWeixinFollowSuccessConversions']) ? $r[$item->eventDate]['monthWeixinFollowSuccessConversions'] : 0;
                    $r[$item->eventDate]['monthWeixinFollowSuccessConversions'] = $r[$item->eventDate]['monthWeixinFollowSuccessConversions'] + $item->monthWeixinFollowSuccessConversions;
                    //真实加粉成功数
                    $r[$item->eventDate]['trueWeixinFollowSuccessConversions'] = (isset($trueWeixin[$item->eventDate]->trueWeixinFollowSuccessConversions)) ? $trueWeixin[$item->eventDate]->trueWeixinFollowSuccessConversions : 0;
                }
                foreach ($data as $item) {
                    //后台币的现金成本
                    $r[$item->eventDate]['money_cost'] = $r[$item->eventDate]['cost'] / ((100 + $projectFirst->per) / 100);
                    //日现金加粉成本
                    $r[$item->eventDate]['money_trueWeixinFollowSuccessConversions'] = 0;
                    $r[$item->eventDate]['money_trueWeixinFollowSuccessConversions'] = ($r[$item->eventDate]['trueWeixinFollowSuccessConversions'] == 0 || $r[$item->eventDate]['cost'] == 0) ? 0 : ($r[$item->eventDate]['cost'] / ((100 + $projectFirst->per) / 100)) / $r[$item->eventDate]['trueWeixinFollowSuccessConversions'];
                    //添加率
                    $r[$item->eventDate]['add_rate'] =$r[$item->eventDate]['weixinFollowSuccessConversions']==0?0: $r[$item->eventDate]['trueWeixinFollowSuccessConversions'] / $r[$item->eventDate]['weixinFollowSuccessConversions'];
                }
                ksort($r);
                $r1 = [];
                foreach ($r as $k => $v) {
                    if ($v['cost'] == 0 && $v['weiXinCopyConversions'] == 0 && count($r1) == 0) {
                        continue;
                    }
                    $r1[$k] = $v;
                }
                krsort($r1);
                $reportData['r'] = $r1;
                //后台消费总数
                $reportData['cost_sum'] = array_sum(array_column($data, 'cost'));
                //后台币的现金成本总数
                $reportData['money_cost_sum'] = array_sum(array_column($r, 'money_cost'));
                //后台微信加粉量总数
                $reportData['weixinFollowSuccessConversions_sum'] = array_sum(array_column($data, 'weixinFollowSuccessConversions'));
                //后台成本总数
                $reportData['cost_weixin_sum'] = $reportData['weixinFollowSuccessConversions_sum'] == 0 ? 0 : ($reportData['cost_sum'] / $reportData['weixinFollowSuccessConversions_sum']);
                //实际加粉量总数
                $reportData['trueWeixinFollowSuccessConversions'] = array_sum(array_column($r, 'trueWeixinFollowSuccessConversions'));
                //现金加粉成本
                $reportData['money_sum'] =$reportData['trueWeixinFollowSuccessConversions']==0?0: ($reportData['cost_sum'] / ((100 + $projectFirst->per) / 100))/$reportData['trueWeixinFollowSuccessConversions'];
                return view("datareport/list", ['data' => $reportData]);
            }
        }
    }
    //输出加粉的报表
    public function list(Request $request)
    {
        if ($request->method() == 'POST') {
            return json(['code' => 0, 'msg' => $request->post()]);
        } else {
            // 如果会话中没有 CSRF Token，则生成一个
            if (!$request->session()->has('csrf_token')) {
                $request->session()->put('csrf_token', md5(uniqid()));
            }

            $reportData = [];
            $reportData['csrf_token'] = $request->session()->get('csrf_token');

            $date = $request->get('date', date("Y-m-d H:i:s", strtotime("-1 day")));
            $date1 = new DateTime($date);
            // 修改日期为该月的第一天
            $startDay = $date1->format('Y-m-01');

            //月初第一天
            $reportData['firstdayDate'] = $startDay;
            $projectid = intval(mcrypt_decode($request->get('project', '4226TI-4csKBv_qPfwJDaOUItH9hx3FfuENLqoINnkVIUFDf94aNWf_0VcpiApvipH12Q3FNtWKyGkY')));
            $projectFirst = Db::table('baidu_xinxiliu_project')->where('id', $projectid)->first();

            if ($projectFirst) {
                $reportData['curdate'] = $date;
                $reportData['projectName'] = $projectFirst->clientName;
                $project = Db::table('baidu_xinxiliu_project')->where('clientName', $projectFirst->clientName)->limit(200)->get()->toArray();
                $project_id_data = [];
                foreach ($project as $item) {
                    $project_id_data[$item->id] = $item;
                }
                $project_ids = array_column($project, 'id');
                //真实加粉数据
                $trueWeixinData = Db::table('baidu_xinxiliu_project_trueweixinfollow')
                    ->where('projectName', $projectFirst->clientName)
                    ->where('eventDate', '>=', $startDay)
                    ->where('eventDate', '<=', $date)
                    ->get()->toArray();
                $trueWeixin = [];
                foreach ($trueWeixinData as $v) {
                    $trueWeixin[$v->eventDate] = $v;
                }
                $data = Db::table('baidu_xinxiliu_project_reportdata')
                    ->where('eventDate', '>=', $startDay)
                    ->where('eventDate', '<=', $date)
                    ->whereIn('projectId', $project_ids)
                    ->orderBy('eventDate', 'DESC')
                    ->get()->toArray();

                $r = [];
                foreach ($data as $item) {
                    $r[$item->eventDate]['projectName'] = $item->projectName;
                    $r[$item->eventDate]['cost'] = isset($r[$item->eventDate]['cost']) ? $r[$item->eventDate]['cost'] : 0;

                    $r[$item->eventDate]['cost'] = $r[$item->eventDate]['cost'] + $item->cost;

                    $r[$item->eventDate]['weiXinCopyConversions'] = isset($r[$item->eventDate]['weiXinCopyConversions']) ? $r[$item->eventDate]['weiXinCopyConversions'] : 0;
                    $r[$item->eventDate]['weiXinCopyConversions'] = $r[$item->eventDate]['weiXinCopyConversions'] + $item->weiXinCopyConversions;

                    $r[$item->eventDate]['monthWeiXinCopyConversions'] = isset($r[$item->eventDate]['monthWeiXinCopyConversions']) ? $r[$item->eventDate]['monthWeiXinCopyConversions'] : 0;
                    $r[$item->eventDate]['monthWeiXinCopyConversions'] = $r[$item->eventDate]['monthWeiXinCopyConversions'] + $item->monthWeiXinCopyConversions;

                    $r[$item->eventDate]['ctWeiXinCopyConversions'] = isset($r[$item->eventDate]['ctWeiXinCopyConversions']) ? $r[$item->eventDate]['ctWeiXinCopyConversions'] : 0;
                    $r[$item->eventDate]['ctWeiXinCopyConversions'] = $r[$item->eventDate]['ctWeiXinCopyConversions'] + $item->monthWeiXinCopyConversions;

                    $r[$item->eventDate]['weixinFollowSuccessConversions'] = isset($r[$item->eventDate]['weixinFollowSuccessConversions']) ? $r[$item->eventDate]['weixinFollowSuccessConversions'] : 0;
                    $r[$item->eventDate]['weixinFollowSuccessConversions'] = $r[$item->eventDate]['weixinFollowSuccessConversions'] + $item->weixinFollowSuccessConversions;

                    //日的消耗币/加粉成功价
                    $r[$item->eventDate]['cost_weixin'] = isset($r[$item->eventDate]['cost_weixin']) ? $r[$item->eventDate]['cost_weixin'] : 0;;
                    $r[$item->eventDate]['cost_weixin'] = $item->weixinFollowSuccessConversions == 0 ? 0 : $r[$item->eventDate]['cost_weixin'] + ($item->cost / $item->weixinFollowSuccessConversions);


                    $r[$item->eventDate]['monthWeixinFollowSuccessConversions'] = isset($r[$item->eventDate]['monthWeixinFollowSuccessConversions']) ? $r[$item->eventDate]['monthWeixinFollowSuccessConversions'] : 0;
                    $r[$item->eventDate]['monthWeixinFollowSuccessConversions'] = $r[$item->eventDate]['monthWeixinFollowSuccessConversions'] + $item->monthWeixinFollowSuccessConversions;
                    //真实加粉成功数
                    $r[$item->eventDate]['trueWeixinFollowSuccessConversions'] = (isset($trueWeixin[$item->eventDate]->trueWeixinFollowSuccessConversions)) ? $trueWeixin[$item->eventDate]->trueWeixinFollowSuccessConversions : 0;
                }
                foreach ($data as $item) {
                    //后台币的现金成本
                    $r[$item->eventDate]['money_cost'] = $r[$item->eventDate]['cost'] / ((100 + $projectFirst->per) / 100);
                    //日现金加粉成本
                    $r[$item->eventDate]['money_trueWeixinFollowSuccessConversions'] = 0;
                    $r[$item->eventDate]['money_trueWeixinFollowSuccessConversions'] = ($r[$item->eventDate]['trueWeixinFollowSuccessConversions'] == 0 || $r[$item->eventDate]['cost'] == 0) ? 0 : ($r[$item->eventDate]['cost'] / ((100 + $projectFirst->per) / 100)) / $r[$item->eventDate]['trueWeixinFollowSuccessConversions'];
                    //添加率
                    $r[$item->eventDate]['add_rate'] =$r[$item->eventDate]['weixinFollowSuccessConversions']==0?0: $r[$item->eventDate]['trueWeixinFollowSuccessConversions'] / $r[$item->eventDate]['weixinFollowSuccessConversions'];
                }
                ksort($r);
                $r1 = [];
                foreach ($r as $k => $v) {
                    if ($v['cost'] == 0 && $v['weiXinCopyConversions'] == 0 && count($r1) == 0) {
                        continue;
                    }
                    $r1[$k] = $v;
                }
                krsort($r1);
                $reportData['r'] = $r1;
                //后台消费总数
                $reportData['cost_sum'] = array_sum(array_column($data, 'cost'));
                //后台币的现金成本总数
                $reportData['money_cost_sum'] = array_sum(array_column($r, 'money_cost'));
                //后台微信加粉量总数
                $reportData['weixinFollowSuccessConversions_sum'] = array_sum(array_column($data, 'weixinFollowSuccessConversions'));
                //后台成本总数
                $reportData['cost_weixin_sum'] = $reportData['weixinFollowSuccessConversions_sum'] == 0 ? 0 : ($reportData['cost_sum'] / $reportData['weixinFollowSuccessConversions_sum']);
                //实际加粉量总数
                $reportData['trueWeixinFollowSuccessConversions'] = array_sum(array_column($r, 'trueWeixinFollowSuccessConversions'));
                //现金加粉成本
                $reportData['money_sum'] =$reportData['trueWeixinFollowSuccessConversions']==0?0: ($reportData['cost_sum'] / ((100 + $projectFirst->per) / 100))/$reportData['trueWeixinFollowSuccessConversions'];
                return view("datareport/list", ['data' => $reportData]);
            }
        }
    }

    //输出表单的报表
    public function show(Request $request)
    {
        $projectid = intval(mcrypt_decode($request->get('project', '4226TI-4csKBv_qPfwJDaOUItH9hx3FfuENLqoINnkVIUFDf94aNWf_0VcpiApvipH12Q3FNtWKyGkY')));
        $date = $request->get('date', date('Y-m-d', strtotime("-1 day")));
        $projectFirst = Db::table('baidu_xinxiliu_project')->where('id', $projectid)->first();
        if ($projectFirst) {
            $reportData = [];
            $reportData['curdate'] = $date;
            $reportData['projectName'] = $projectFirst->clientName;
            $project = Db::table('baidu_xinxiliu_project')->where('clientName', $projectFirst->clientName)->limit(200)->get()->toArray();
            $project_id_data = [];
            foreach ($project as $item) {
                $project_id_data[$item->id] = $item;
            }
            $project_ids = array_column($project, 'id');
            $data = Db::table('baidu_xinxiliu_project_reportdata')->where('eventDate', $date)->whereIn('projectId', $project_ids)->get()->toArray();
            foreach ($data as $index => $item) {
                $data[$index]->subName = $project_id_data[$item->projectId]->subName;
                //日的消耗币/表单价
                $data[$index]->cost_feed = $item->feedOCPCConversionsDetail3 == 0 ? 0 : $item->cost / $item->feedOCPCConversionsDetail3;
                //月的消耗币/表单价
                $data[$index]->month_cost_feed = $item->monthFeedOCPCConversionsDetail3 == 0 ? 0 : $item->monthCost / $item->monthFeedOCPCConversionsDetail3;
            }
            //昨日所有账户消耗币汇总
            $reportData['yseterday_cost_sum'] = array_sum(array_column($data, 'cost'));
            //昨日所有账户表单价汇总
            $reportData['yseterday_feedOCPCConversionsDetail3_sum'] = array_sum(array_column($data, 'feedOCPCConversionsDetail3'));

            //昨日所有账户消耗币/表单价
            $reportData['yseterday_cost_feed_sum'] = $reportData['yseterday_feedOCPCConversionsDetail3_sum'] == 0 ? 0 : ($reportData['yseterday_cost_sum'] / $reportData['yseterday_feedOCPCConversionsDetail3_sum']);

            //月所有账户消耗币汇总
            $reportData['month_cost_sum'] = array_sum(array_column($data, 'monthCost'));
            //月所有账户表单价汇总
            $reportData['month_feedOCPCConversionsDetail3_sum'] = array_sum(array_column($data, 'monthFeedOCPCConversionsDetail3'));
            //月所有账户消耗币/表单价
            $reportData['month_cost_feed_sum'] = $reportData['month_feedOCPCConversionsDetail3_sum'] == 0 ? 0 : $reportData['month_cost_sum'] / $reportData['month_feedOCPCConversionsDetail3_sum'];
            //现金是否显示,默认不显示
            $reportData['show_money'] = $request->get('m', '');
            //昨日现金
            $reportData['yesterday_money'] = $reportData['yseterday_cost_feed_sum'] / ((100 + $projectFirst->per) / 100);
            //月现金
            $reportData['month_money'] = $reportData['month_cost_feed_sum'] / ((100 + $projectFirst->per) / 100);
            $reportData['data'] = $data;

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
            //昨天
            $reportData['yesterdayDate'] = date('Y-m-d', strtotime("-1 day"));
            //月初第一天
            $date = new DateTime();
            $date->modify('-1 day');
            // 获取输入日期的年份和月份
            $year = $date->format('Y');
            $month = $date->format('m');
            // 设置循环开始日期为当月的第一天
            $startDay = new DateTime("first day of this month $year-$month");
            //月初第一天
            $reportData['firstdayDate'] = $startDay->format('Y-m-d');
            //return json(['code' => 1, 'msg' => $reportData]);
            return view("datareport/show", ['data' => $reportData]);
        }

        return json(['code' => 1, 'msg' => ['查询失败']]);
    }


    public function update(Request $request)
    {
        foreach ($request->post() as $key => $v) {
            $position = strpos($key, '_1_');
            if ($position !== false) {
                list($projectName, $eventDate) = explode('_1_', $key);
                //当天数据要是没有就创建表数据
                $is_exist = Db::table('baidu_xinxiliu_project_trueweixinfollow')
                    ->where(['projectName' => $projectName, 'eventDate' => $eventDate])
                    ->exists();
                if (!$is_exist) {
                    Db::table('baidu_xinxiliu_project_trueweixinfollow')->insert(['projectName' => $projectName, 'eventDate' => $eventDate, 'trueWeixinFollowSuccessConversions' => $v]);
                } else {
                    DB::table('baidu_xinxiliu_project_trueweixinfollow')
                        ->where(['projectName' => $projectName, 'eventDate' => $eventDate])
                        ->update(['trueWeixinFollowSuccessConversions' => $v]);
                }
            }
        }
        return json(['code' => 0, 'msg' => $request->post()]);
    }

    function report($userName, $reportType, $day, $columns)
    {
        $xinxiliu_refreshToken = DB::table('baidu_xinxiliu_refreshToken')->where('userId', 56325868)->first();
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

    //表单加粉户日耗统计
    public function jiafen(Request $request)
    {

        $date = $request->get('date', date('Y-m-d'));
        $user = [
            "R-文中子",
            "R-及老庄",
            "R-古今史",
            "R-彼不教",
            "R-辽与金",
            "R-勿违背",
            "R-有左氏",
            "R-人之伦",
            "R-有虫鱼",
            "R-光于前",
            "R-我周公",
            "R-应乎中",
            "QD-能赋棋",
            "QD-泌七岁",
            "R-知某数",
            "QD-泌七岁",
            "QD-能赋棋",
            "RQYZ-及汉周",
            "RQYZ-赵中令",
            "RQYZ-若梁灏",
            "RQYZ-苏老泉",
            "R-犹苦卓",
            "RQYZ-有誓命",
            "RQYZ-事虽小",
            "RQYZ-勿擅为",
            "R-贻亲忧",
            "R-乘下车",
            "R-勿拣择",
            "R-如事生",
            "RQYZ-信为先",
            "R-勿触棱",
            "QD-反必面",
            "R-拜恭敬"
        ];
        $data_subuser = Db::table("baidu_xinxiliu_subuser")->select('userName', 'userId')->whereIn('userName', $user)->get()->toArray();
        $data_subuser_userIds = array_column($data_subuser, 'userId');
        $subuser_list = [];
        foreach ($data_subuser as $subuser) {
            $subuser_list[$subuser->userId] = $subuser->userName;
        }
        $project=Db::table("baidu_xinxiliu_project")->select('clientName','id', 'subName')->whereIn('subName', $user)->get()->toArray();
        $project_list = [];
        foreach ($project as $p) {
            $project_list[$p->subName] = $p->clientName;
        }

        $data = Db::table("baidu_xinxiliu_reportdata")
            ->where('eventDate', $date)
            ->whereIn('userId', $data_subuser_userIds)->get()->toArray();
        foreach ($data as $index => $item){
            $data[$index]->projetName= isset($project_list[$subuser_list[$item->userId]])?$project_list[$subuser_list[$item->userId]]:'';
        }
        //总共消耗
        $total_cost = number_format(array_sum(array_column($data, 'cost')), 2);
        foreach ($data as $index => $item) {
            $data[$index]->userName = $subuser_list[$item->userId];
        }
        $highlight = $request->get('highlight', '');
        if (!$highlight)
            return raw_view('index/json');
        else
            return json(['code' => 0, 'msg' => ['total_cost' => $total_cost, 'data' => $data]]);

    }
}