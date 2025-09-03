<?php

namespace app\controller;

use support\Request;
use support\Db;
use Workerman\Protocols\Http\Chunk;

class FeedController
{
    protected $userName;

    public function __construct()
    {
        $this->userName = Request()->get('userName', 'R-锦时筑梦');
    }
    //获取线索信息
    public function getNoticeList(Request $request){
        $url='https://api.baidu.com/json/sms/service/LeadsNoticeService/getNoticeList';

        $project = Db::table('baidu_xinxiliu_project')
            ->leftJoin('baidu_xinxiliu_subuser', 'baidu_xinxiliu_project.subName', '=', 'baidu_xinxiliu_subuser.userName')
            ->leftJoin('baidu_xinxiliu_refreshToken', 'baidu_xinxiliu_subuser.masterUid', '=', 'baidu_xinxiliu_refreshToken.userId')
            ->where('baidu_xinxiliu_project.subName', $this->userName)->first();
        $options = [
            'max_conn_per_addr' => 512, // 每个域名最多维持多少并发连接
            'keepalive_timeout' => 15,  // 连接多长时间不通讯就关闭
            'connect_timeout' => 30,  // 连接超时时间
            'timeout' => 30,  // 请求发出后等待响应的超时时间
        ];
        $http = new \Workerman\Http\Client($options);
        $user_payload = array(
            "header" => array(
                "userName" => $this->userName,
                "accessToken" => $project->accessToken,
                "action" => "API-PYTHON"
            ),
        );
        $user_payload['body'] = [
            'solutionType' => 'form',
            'startDate'=>date('Y-m-d', strtotime("-7 day")),
            'endDate' => date('Y-m-d'),

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
                        var_dump($response->getBody()->getContents());
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
    public function queryFeedOutline(Request $request){
        $url='https://api.baidu.com/json/sms/service/AdviceService/queryFeedOutline';
        $project = Db::table('baidu_xinxiliu_project')
            ->leftJoin('baidu_xinxiliu_subuser', 'baidu_xinxiliu_project.subName', '=', 'baidu_xinxiliu_subuser.userName')
            ->leftJoin('baidu_xinxiliu_refreshToken', 'baidu_xinxiliu_subuser.masterUid', '=', 'baidu_xinxiliu_refreshToken.userId')
            ->where('baidu_xinxiliu_project.subName', $this->userName)->first();
        $options = [
            'max_conn_per_addr' => 512, // 每个域名最多维持多少并发连接
            'keepalive_timeout' => 15,  // 连接多长时间不通讯就关闭
            'connect_timeout' => 30,  // 连接超时时间
            'timeout' => 30,  // 请求发出后等待响应的超时时间
        ];
        $http = new \Workerman\Http\Client($options);
        $user_payload = array(
            "header" => array(
                "userName" => $this->userName,
                "accessToken" => $project->accessToken,
                "action" => "API-PYTHON"
            ),
        );
        $user_payload['body'] = [
            'adviceKeys' => ['adGroupAutoOrientation','addKeywordFeed','modAdGroupPriceFeed','addFeedCreativeConversion','modCampaignAutoBidFeed'],
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
    //查询计划
    public function getCampaignFeed(Request $request)
    {
        $url = 'https://api.baidu.com/json/feed/v1/CampaignFeedService/getCampaignFeed';
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
        // ['campaignFeedId', 'campaignFeedName', 'subject', 'appinfo', 'budget', 'starttime', 'endtime', 'schedule',
        //'pause', 'status', 'bstype', 'campaignType', 'addtime', 'eshopType', 'shadow', 'budgetOfflineTime', 'rtaStatus', 'bid', 'bidtype', 'ftypes', 'ocpc', 'unefficientCampaign', 'campaignOcpxStatus', 'inheritAscriptionType', 'inheritUserids', 'inheritCampaignInfos', 'bmcUserId', 'catalogId', 'productType', 'projectFeedId', 'useLiftBudget', 'liftBudget', 'liftStatus', 'deliveryType', 'appSubType', 'miniProgramType', 'bidMode', 'productIds', 'saleType'],
        $user_payload['body'] = [
            'campaignFeedFields' => ['campaignFeedId', 'campaignFeedName', 'subject', 'appinfo', 'budget', 'starttime', 'endtime', 'schedule', 'pause', 'status', 'bstype', 'campaignType', 'addtime', 'eshopType', 'shadow', 'budgetOfflineTime', 'rtaStatus', 'bid', 'bidtype', 'ftypes', 'ocpc', 'unefficientCampaign', 'campaignOcpxStatus', 'inheritAscriptionType', 'inheritUserids', 'inheritCampaignInfos', 'productType', 'projectFeedId', 'useLiftBudget', 'liftBudget', 'liftStatus', 'deliveryType', 'appSubType', 'miniProgramType', 'bidMode', 'productIds'],
            'campaignFeedIds' => [],
            'campaignFeedFilter' => [
                'bstype' => [],
            ]
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
                        $this->addCampaignFeed($feedData['body']['data']);
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

    //新建计划https://api.baidu.com/json/feed/v1/CampaignFeedService/addCampaignFeed
    public function addCampaignFeed($data)
    {
        foreach ($data as $value) {
            $campaignFeedTypes = [
                'campaignFeedName' => $value['campaignFeedName'],//信息流计划名称
                'subject' => $value['subject'],//营销目标
                //'appinfo' =>  $value['appinfo'],
                'budget' => $value['budget'],//推广计划预算
                'starttime' => date("Y-m-d"),//推广开始时间
                'endtime' => date("Y-m-d",strtotime("+300 day")),//推广结束时间
               // 'schedule' => $value['schedule'],//推广计划暂停时段
                'pause' => true,//计划启停, 默认启停
                'bstype' => $value['bstype'],//物料类型
                'campaignType' => $value['campaignType'],//信息流计划类型
                'ftypes' => $value['ftypes'],//流量类型
                'bidtype' => $value['bidtype'],//出价方式
                'bid' => $value['bid'],
                //'ocpc' => $value['ocpc'],
                'useLiftBudget' => $value['useLiftBudget'],
               // 'deliveryType' => $value['deliveryType'],
                //'projectFeedId' => $value['projectFeedId'],
                //'productType' => $value['productType'],
                //'bidMode' => $value['bidMode'],
            ];
            $url = 'https://api.baidu.com/json/feed/v1/CampaignFeedService/addCampaignFeed';

            $http = new \Workerman\Http\Client();
            $xinxiliu_refreshToken = DB::table('baidu_xinxiliu_refreshToken')->where('userId', 56325868)->first();
            $user_payload = array(
                "header" => array(
                    "userName" => 'R-我周公',
                    "accessToken" => $xinxiliu_refreshToken->accessToken,
                    "action" => "API-PYTHON"
                ),
                "body"=>array(
                    "campaignFeedTypes"=>$campaignFeedTypes,
                )
            );
            var_dump($user_payload);exit;
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
                'success' => function ($response) {
                    var_dump($response);
                    if ($response->getStatusCode() == 200 && $response->getBody()->getSize() > 0) {
                        $feedData = json_decode($response->getBody()->getContents(), true);
                        var_dump($feedData);
                        if (is_array($feedData) && $feedData['header']['desc'] == 'success') {
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
        }


    }

    //查询单元
    public function getAdgroupFeed(Request $request)
    {
        $url = 'https://api.baidu.com/json/feed/v1/AdgroupFeedService/getAdgroupFeed';
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
            'adgroupFeedFields' => ['adgroupFeedId', 'campaignFeedId', 'adgroupFeedName', 'pause', 'status', 'bid', 'ftypes', 'bidtype', 'ocpc', 'atpFeedId', 'addtime', 'modtime', 'deliveryType', 'unefficientAdgroup', 'productSetId', 'ftypeSelection', 'bidSource', 'unitOcpxStatus', 'atpName'],
            'ids' => [566330401, 567019948, 568279135, 568050602, 568279134, 567019946, 567019947, 567019941],
            'idType' => 1,
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

    //查询创意https://api.baidu.com/json/feed/v1/CreativeFeedService/getCreativeFeed
    public function getAdgroupFeedByCampaignFeedId(Request $request)
    {
        $url = 'https://api.baidu.com/json/feed/v1/CreativeFeedService/getCreativeFeed';
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
            'creativeFeedFields' => ['creativeFeedId', 'adgroupFeedId', 'materialstyle', 'creativeFeedName', 'pause', 'material', 'status', 'refusereason', 'expmask', 'changeorder', 'commentnum', 'readnum', 'playnum', 'ideaType', 'showMt', 'addtime', 'progFlag', 'approvemsgnew', 'auditTimeModel', 'template', 'huitus',],
            'ids' => [566330401, 567019948, 568279135, 568050602, 568279134, 567019946, 567019947, 567019941],
            'idType' => 1,
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

    //批量下载https://api.baidu.com/json/feed/v1/BulkJobFeedService/getAllFeedObjects
    public function getAllFeedObjects(Request $request)
    {
        $url = 'https://api.baidu.com/json/feed/v1/BulkJobFeedService/getAllFeedObjects';
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
            'format' => 0,
            'feedAccountFields' => ['all'],
            'feedCampaignFields' => ['all'],
            'feedAdgroupFields' => ['all'],
            'feedCreativeFields' => ['all'],
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

    //查询文件生成状态https://api.baidu.com/json/feed/v1/BulkJobFeedService/getFileStatus
    public function getFileStatus(Request $request)
    {
        $url = 'https://api.baidu.com/json/feed/v1/BulkJobFeedService/getFileStatus';
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
            'fileId' => '390c7f63ca76bac908e849ca01ff7e97',
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

    //获取生成文件路径https://api.baidu.com/json/feed/v1/BulkJobFeedService/getFilePath
    public function getFilePath(Request $request)
    {
        $url = 'https://api.baidu.com/json/feed/v1/BulkJobFeedService/getFilePath';
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
            'fileId' => '390c7f63ca76bac908e849ca01ff7e97',
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

    //查询转化追踪 https://api.baidu.com/json/feed/v1/SearchFeedService/getOcpcTransFeed
    public function getOcpcTransFeed(Request $request)
    {
        $url = 'https://api.baidu.com/json/feed/v1/SearchFeedService/getOcpcTransFeed';
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
            'transFrom' => 2,
            'ocpcLevel' => 2,
            'isNewVersion' => 1,
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

}