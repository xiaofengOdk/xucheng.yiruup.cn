<?php

namespace app\controller;

use DateTime;
use support\Request;
use support\Db;
use Workerman\Protocols\Http\Chunk;

class HistoryController
{
    protected $userName;

    public function __construct()
    {
        $this->userName = Request()->get('userName', 'R-人之伦');
    }

    public function index(Request $request)
    {
        $url = 'https://api.baidu.com/json/sms/service/ToolkitService/getOperationRecord';
        $options = [
            'max_conn_per_addr' => 512, // 每个域名最多维持多少并发连接
            'keepalive_timeout' => 15,  // 连接多长时间不通讯就关闭
            'connect_timeout' => 30,  // 连接超时时间
            'timeout' => 30,  // 请求发出后等待响应的超时时间
        ];
        $http = new \Workerman\Http\Client($options);
        $xinxiliu_refreshToken = DB::table('baidu_xinxiliu_refreshToken')->where('userId', 56325868)->first();
        $user_payload = array(
            "header" => array(
                "userName" => $this->userName,
                "accessToken" => $xinxiliu_refreshToken->accessToken,
                "action" => "API-PYTHON"
            ),
        );
        // 获取当前日期
        $today = new DateTime();

        // 减去三个月
        $threeMonthsAgo = clone $today;
        $threeMonthsAgo->modify('-3 months');

        // 格式化日期为 YYYY-MM-DD
        $threeMonthsAgo = $threeMonthsAgo->format('Y-m-d');
        $user_payload['body'] = [
            "startDate" => $threeMonthsAgo,
            "endDate" => date('Y-m-d'),
            "optTypes" => [2],
            'materials'=>[],
            'optContents'=>[],

            /*
             * 信息流推广操作层级（recordType=2）：
                1-推广单元
                2-推广计划
                3-帐户
                4-创意
                31-定向辅助
                41-组件
                45-转化追踪工具*/
            "optLevel" => 3,
            "recordType" => 2
        ];
        $connection = $request->connection;
        $http->request($url, [
            'method' => 'POST',
            'version' => '1.1',
            'headers' => [
                "Accept-Encoding" => "gzip, deflate",
                "Accept" => "application/json",
                'Connection' => 'keep-alive',
                'Content-Type' => 'application/json',
            ],
            'data' => json_encode($user_payload),
            'success' => function ($response) use ($connection) {
                if ($response->getStatusCode() == 200 && $response->getBody()->getSize() > 0) {
                    $feedData = json_decode($response->getBody()->getContents(), true);
                    if (is_array($feedData) && $feedData['header']['desc'] == 'success') {
                        $connection->send(new Chunk($response->getBody()));
                        $connection->send(new Chunk('')); // 发送空的的chunk代表resonse结束
                    } else {
                        var_dump($feedData);
                    }
                } else {
                    var_dump($response->getBody()->getContents());
                }
            },
            'error' => function ($exception) {
                var_dump($exception->getMessage);
            }
        ]);
        return response()->withHeaders([
            "Transfer-Encoding" => "chunked",
        ]);

    }
    //信息流查询建议概览
    public function queryFeedOutline(Request $request)
    {
        $url='https://api.baidu.com/json/sms/service/AdviceService/queryFeedOutline';
        $options = [
            'max_conn_per_addr' => 512, // 每个域名最多维持多少并发连接
            'keepalive_timeout' => 15,  // 连接多长时间不通讯就关闭
            'connect_timeout' => 30,  // 连接超时时间
            'timeout' => 30,  // 请求发出后等待响应的超时时间
        ];
        $http = new \Workerman\Http\Client($options);
        $xinxiliu_refreshToken = DB::table('baidu_xinxiliu_refreshToken')->where('userId', 56325868)->first();
        $user_payload = array(
            "header" => array(
                "userName" => $this->userName,
                "accessToken" => $xinxiliu_refreshToken->accessToken,
                "action" => "API-PYTHON"
            ),
        );

        $user_payload['body'] = [
            "adviceKeys" =>[ "adGroupAutoOrientation",
                "addKeywordFeed",
                "modAdGroupPriceFeed",
                "addFeedCreativeConversion",
                "modCampaignAutoBidFeed"]
        ];
        $connection = $request->connection;
        $http->request($url, [
            'method' => 'POST',
            'version' => '1.1',
            'headers' => [
                "Accept-Encoding" => "gzip, deflate",
                "Accept" => "application/json",
                'Connection' => 'keep-alive',
                'Content-Type' => 'application/json',
            ],
            'data' => json_encode($user_payload),
            'success' => function ($response) use ($connection) {
                if ($response->getStatusCode() == 200 && $response->getBody()->getSize() > 0) {
                    $feedData = json_decode($response->getBody()->getContents(), true);
                    var_dump($feedData);
                    if (is_array($feedData) && $feedData['header']['desc'] == 'success') {
                        $connection->send(new Chunk($response->getBody()));
                        $connection->send(new Chunk('')); // 发送空的的chunk代表resonse结束
                    } else {
                        var_dump($feedData);
                    }
                } else {
                    var_dump($response->getBody()->getContents());
                }
            },
            'error' => function ($exception) {
                var_dump($exception->getMessage);
            }
        ]);
        return response()->withHeaders([
            "Transfer-Encoding" => "chunked",
        ]);
    }

    //获取添加意图词获得更多转化优化详情https://api.baidu.com/json/sms/service/AdviceService/
    function queryFeedDetail(Request $request)
    { $url='https://api.baidu.com/json/sms/service/AdviceService/';
        $options = [
            'max_conn_per_addr' => 512, // 每个域名最多维持多少并发连接
            'keepalive_timeout' => 15,  // 连接多长时间不通讯就关闭
            'connect_timeout' => 30,  // 连接超时时间
            'timeout' => 30,  // 请求发出后等待响应的超时时间
        ];
        $http = new \Workerman\Http\Client($options);
        $xinxiliu_refreshToken = DB::table('baidu_xinxiliu_refreshToken')->where('userId', 56325868)->first();
        $user_payload = array(
            "header" => array(
                "userName" => $this->userName,
                "accessToken" => $xinxiliu_refreshToken->accessToken,
                "action" => "API-PYTHON"
            ),
        );

        $user_payload['body'] = [
            "adviceKeys" =>[ "adGroupAutoOrientation",
                "addKeywordFeed",
                "modAdGroupPriceFeed",
                "addFeedCreativeConversion",
                "modCampaignAutoBidFeed"]
        ];
        $connection = $request->connection;
        $http->request($url, [
            'method' => 'POST',
            'version' => '1.1',
            'headers' => [
                "Accept-Encoding" => "gzip, deflate",
                "Accept" => "application/json",
                'Connection' => 'keep-alive',
                'Content-Type' => 'application/json',
            ],
            'data' => json_encode($user_payload),
            'success' => function ($response) use ($connection) {
                if ($response->getStatusCode() == 200 && $response->getBody()->getSize() > 0) {
                    $feedData = json_decode($response->getBody()->getContents(), true);
                    var_dump($feedData);
                    if (is_array($feedData) && $feedData['header']['desc'] == 'success') {
                        $connection->send(new Chunk($response->getBody()));
                        $connection->send(new Chunk('')); // 发送空的的chunk代表resonse结束
                    } else {
                        var_dump($feedData);
                    }
                } else {
                    var_dump($response->getBody()->getContents());
                }
            },
            'error' => function ($exception) {
                var_dump($exception->getMessage);
            }
        ]);
        return response()->withHeaders([
            "Transfer-Encoding" => "chunked",
        ]);

    }
    //查询单元诊断-关键因素分析详情https://api.baidu.com/json/sms/service/FeedDiagnosisService/queryFeedUnitDiagnosisDetail
    function queryFeedUnitDiagnosisDetail(Request $request)
    {

    }
}