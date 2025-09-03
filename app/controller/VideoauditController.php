<?php

namespace app\controller;

use support\Db;
use support\Request;

//百度信息流 视频预检
class VideoauditController
{

    protected $userName;

    public function __construct()
    {
        $this->userName = Request()->get('userName', 'R-有虫鱼');
    }
    //查询审核业务
    public function queryTask(Request $request)
    {
        //https://api.baidu.com/json/sms/service/VideoAuditAPI/queryTask
        $xinxiliu_refreshToken = DB::table('baidu_xinxiliu_refreshToken')->where('userId', 56325868)->first();
        $user_payload = array(
            "header" => array(
                "userName" => $this->userName,
                "accessToken" => $xinxiliu_refreshToken->accessToken,
                "action" => "API-PYTHON"
            ),
        );
        $user_payload['body'] = [
            'page'=>array(
                'offset'=>0,
                'numbers'=>200
            )
        ];
        $jsonData = json_encode($user_payload);
        $response = getData('https://api.baidu.com/json/sms/service/VideoAuditAPI/queryTask', $jsonData);
        $response = json_decode($response, true);
        return json($response);
    }


    //创建审核任务
    public function createAuditTask(Request $request)
    {
        //https://api.baidu.com/json/sms/service/VideoAuditAPI/createAuditTask
        $xinxiliu_refreshToken = DB::table('baidu_xinxiliu_refreshToken')->where('userId', 56325868)->first();
        $user_payload = array(
            "header" => array(
                "userName" => $this->userName,
                "accessToken" => $xinxiliu_refreshToken->accessToken,
                "action" => "API-PYTHON"
            ),
        );
        $user_payload['body'] = [
            'channel' => 2,
            'videoIdList'=>[1529765053,1529765778,1529722095,1529722266]
        ];
        $jsonData = json_encode($user_payload);
        $response = getData('https://api.baidu.com/json/sms/service/VideoAuditAPI/createAuditTask', $jsonData);
        $response = json_decode($response, true);
        return json($response);
    }

    //查询今天已经审核的视频个数
    public function queryCount(Request $request)
    {
        //https://api.baidu.com/json/sms/service/VideoAuditAPI/queryCount
        $xinxiliu_refreshToken = DB::table('baidu_xinxiliu_refreshToken')->where('userId', 56325868)->first();
        $user_payload = array(
            "header" => array(
                "userName" => $this->userName,
                "accessToken" => $xinxiliu_refreshToken->accessToken,
                "action" => "API-PYTHON"
            ),
        );
        $user_payload['body'] = [
            'channel' => 2,
        ];
        $jsonData = json_encode($user_payload);
        $response = getData('https://api.baidu.com/json/sms/service/VideoAuditAPI/queryCount', $jsonData);
        $response = json_decode($response, true);
        return json($response);
    }


}