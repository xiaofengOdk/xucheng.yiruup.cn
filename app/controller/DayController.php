<?php
namespace app\controller;

use GuzzleHttp\Client as GuzzleHttp;
use support\Db;
use support\Log;
use support\Request;
use support\Response;
use GuzzleHttp\Client as GuzzleHttp1;
use GuzzleHttp\Promise\Utils;

class DayController
{
    public function index(Request $request)
    {
        // 如果会话中没有 CSRF Token，则生成一个
        if (!$request->session()->has('csrf_token')) {
            $request->session()->put('csrf_token', md5(uniqid()));
        }

        $dayData = [];
        $dayData['csrf_token'] = $request->session()->get('csrf_token');
        $projectid = intval(mcrypt_decode($request->get('project', '4226TI-4csKBv_qPfwJDaOUItH9hx3FfuENLqoINnkVIUFDf94aNWf_0VcpiApvipH12Q3FNtWKyGkY')));
        if($projectid>0){
        $projectFirst = Db::table('baidu_xinxiliu_project')->where('id', $projectid)->first();
        if ($projectFirst) {
            $guzzleClient = new GuzzleHttp1(['timeout' =>10.0]);
            //异步请求的 url 参数数组
            $guzzlePromise=[];
            /*
            SELECT * FROM `baidu_xinxiliu_project` a
                LEFT join baidu_xinxiliu_subuser b on a.subName=b.userName
                LEFT join baidu_xinxiliu_refreshToken c on b.masterUid=c.userId
                where a.clientName='王总-北京京医HPV软文加粉';
            */
            $project = Db::table('baidu_xinxiliu_project')
                ->leftJoin('baidu_xinxiliu_subuser', 'baidu_xinxiliu_project.subName', '=', 'baidu_xinxiliu_subuser.userName')
                ->leftJoin('baidu_xinxiliu_refreshToken', 'baidu_xinxiliu_subuser.masterUid', '=', 'baidu_xinxiliu_refreshToken.userId')
                ->where('baidu_xinxiliu_project.clientName', $projectFirst->clientName)->limit(200)->get();

            $today = date("Y-m-d");
            $columns = ["date", "userId", "userName", "impression", "click", "cost", "ctr", "cpc", "cpm", "phoneButtonClicks", 'feedOCPCConversionsDetail3', 'ctFeedOCPCConversionsDetail3', 'phoneDialUpConversions', 'aggrFormClickSuccess', 'ctAggrFormClickSuccess', 'weiXinCopyConversions', 'ctWeiXinCopyConversions', 'advisoryClueCount', 'ctAdvisoryClueCount', 'weixinFollowSuccessConversions', 'ctWeixinFollowSuccessConversions', 'validConsult', 'ctValidConsult', 'weixinAppInvokeUv', 'ctWeixinAppInvokeUv', 'liveWatchConversions'];
            //subNameId 数组
            $subNameIds = [];
            foreach ($project as $k=>$data) {
                $user_payload = array(
                    "header" => array(
                        "userName" => $data->userName,
                        "accessToken" => $data->accessToken,
                        "action" => "API-PYTHON"
                    ),
                );
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
                     weixinFollowSuccessConversionsCost  微信加粉成功成本
                     weixinFollowSuccessConversionsCVR 微信加粉成功转化率
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

                $user_payload['body'] = [
                    "reportType" => 2172649,
                    "startDate" => $today,
                    "endDate" => $today,
                    "timeUnit" => "DAY",
                    "columns" => $columns,
                    "sorts" => [],
                    "filters" => [],
                    "startRow" => 0,
                    "rowCount" => 200,
                    "needSum" => false
                ];
                $jsonData = json_encode($user_payload);
                $refreshTokenUrl = 'https://api.baidu.com/json/sms/service/OpenApiReportService/getReportData';
                // 创建一组异步请求
                $guzzlePromise['request'.$k] = $guzzleClient->postAsync($refreshTokenUrl,['json' => $user_payload]);
            }
            // 并发发送请求并等待所有请求完成
            $results = Utils::settle($guzzlePromise)->wait();
            // 处理每个请求的结果
            $responseData = [];
            foreach ($results as $key => $result) {
                if ($result['state'] === 'fulfilled') {
                    $reportData=json_decode($result['value']->getBody()->getContents(), true);
                    if (is_array($reportData) && $reportData['header']['desc'] == 'success') {

                        if (isset($reportData['body']['data'][0]['rowCount']) && $reportData['body']['data'][0]['rowCount'] >= 1) {
                            foreach ($reportData['body']['data'][0]['rows'] as $row) {
                                $subNameIds[]=$row['userId'];
                                 Db::table('baidu_xinxiliu_moment_reportdata')->updateOrInsert(
                                    ['subNameId' => $row['userId'], 'currentDay' => $today]
                                );
                                $responseData[] =$row;
                            }
                        }
                    }
                } else {
                    // 请求失败
                    $exception = $result['reason'];
                    $responseData[$key] = [
                        'error' => $exception->getMessage(),
                    ];
                }
            }
            $moment_reportdata=Db::table('baidu_xinxiliu_moment_reportdata')
                ->whereIn('subNameId', $subNameIds)
                ->get()->toArray();
            $moment_reportdata_bysubNameId=[];
            foreach ($moment_reportdata as $v){
                $moment_reportdata_bysubNameId[$v->subNameId]=$v;
            }
            $dayData['cost_money_sum']=$dayData['cost_sum']=$dayData['click_sum']=$dayData['impression_sum']=$dayData['trueWeixinNum_sum']=$dayData['trueWeixinNumMoney_sum']=$dayData['trueWeixinNumTrueMoney_sum']=0;
            foreach ($responseData as $k=>$v){
                //真实加粉数量
                $responseData[$k]['trueWeixinNum']=isset($moment_reportdata_bysubNameId[$v['userId']])?$moment_reportdata_bysubNameId[$v['userId']]->trueWeixinNum:0;
                if( $responseData[$k]['trueWeixinNum']==0) {
                    //加粉成本/币
                    $responseData[$k]['trueWeixinNumMoney'] = 0;
                    //加粉成本/现金
                    $responseData[$k]['trueWeixinNumTrueMoney']=0;
                }else {
                    //加粉成本/币
                    $responseData[$k]['trueWeixinNumMoney'] = round($v['cost'] / $responseData[$k]['trueWeixinNum'], 2);
                    //加粉成本/现金
                    $responseData[$k]['trueWeixinNumTrueMoney']= round($v['cost']/ ((100 + $projectFirst->per) / 100),2);

                }
                //加粉成本/现金
                $responseData[$k]['cost_money']=$v['cost']>0?$v['cost']/ ((100 + $projectFirst->per) / 100):0;
                if( $responseData[$k]['trueWeixinNum']>0)
                    $responseData[$k]['trueWeixinNumTrueMoney']=round($responseData[$k]['trueWeixinNumTrueMoney']/$responseData[$k]['trueWeixinNum'],2);
                $dayData['cost_sum']+=$v['cost'];
                $dayData['cost_money_sum']+=$responseData[$k]['cost_money'];
                $dayData['impression_sum']+=$v['impression'];
                $dayData['click_sum']+=$v['click'];
                $dayData['trueWeixinNum_sum']+=$responseData[$k]['trueWeixinNum'];

            }
            if($dayData['cost_sum']>0&&$dayData['trueWeixinNum_sum']>0){
                $dayData['trueWeixinNumMoney_sum']=round($dayData['cost_sum']/$dayData['trueWeixinNum_sum'],2);
                $dayData['trueWeixinNumTrueMoney_sum']=round($dayData['cost_sum']/ ((100 + $projectFirst->per) / 100)/$dayData['trueWeixinNum_sum'],2);
            }

            $dayData['curdate'] = date('Y-m-d H:i:s');
            $dayData['projectName'] = $projectFirst->clientName;
            $dayData['responseData']=$responseData;
            return view("day/list", ['data' => $dayData]);
        }
        }
    }
    public function update(Request $request)
    {
        $curday=date('Y-m-d');
        foreach ($request->post() as $key => $v) {
                //当天数据要是没有就创建表数据
                $is_exist = Db::table('baidu_xinxiliu_moment_reportdata')
                    ->where(['subNameId' => intval($key), 'currentDay' => $curday])
                    ->exists();
                if (!$is_exist) {
                    Db::table('baidu_xinxiliu_moment_reportdata')->insert(
                        [
                            'subNameId' => intval($key), 'currentDay' => $curday,'trueWeixinNum'=>intval($v)
                        ]);
                } else {
                    DB::table('baidu_xinxiliu_moment_reportdata')
                        ->where([ 'subNameId' => intval($key), 'currentDay' => $curday])
                        ->update(['trueWeixinNum'=>intval($v)]);
                }
        }
        return json(['code' => 0, 'msg' => $request->post()]);
    }
}