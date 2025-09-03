<?php

namespace app\controller;

use support\Log;
use support\Request;
use support\Db;

class BaiduController
{
    public function index(Request $request)
    {
        if ($request->method() == 'GET') {
            $baidu_data = $request->get();
            $youhuashi = Db::table('wa_admins')->select('id', 'username')->get();
            return view('baidu/baidu', [
                'baidu_data' => $baidu_data,
                'youhuashi' => $youhuashi,
            ]);
        } else {
            $userId = DB::table('baidu_xinxiliu')->where('userId', intval($request->post('userId')))->first();
            if ($userId) { //如果存在就更新
                $id = DB::table('baidu_xinxiliu')->where('userId', intval($request->post('userId')))
                    ->update([
                        'appId' => $request->post('appId'),
                        'secretKey' => $request->post('secretKey'),
                        'authCode' => $request->post('authCode'),
                        'signature' => $request->post('signature'),
                        'state' => $request->post('state'),
                        'timestamp' => $request->post('timestamp'),
                        'userId' => intval($request->post('userId')),
                        'adminId' => intval($request->post('adminId')),
                        'updated_at' => date("Y-m-d H:i:s", time()),
                        'status' => 1
                    ]);
            } else {//不存在就入库
                $id = DB::table('baidu_xinxiliu')->insertGetId([
                    'appId' => $request->post('appId'),
                    'secretKey' => $request->post('secretKey'),
                    'authCode' => $request->post('authCode'),
                    'signature' => $request->post('signature'),
                    'state' => $request->post('state'),
                    'timestamp' => $request->post('timestamp'),
                    'userId' => intval($request->post('userId')),
                    'adminId' => intval($request->post('adminId')),
                    'status' => 1,
                    'updated_at' => date("Y-m-d H:i:s", time()),
                    'created_at' => date("Y-m-d H:i:s", time()),
                ]);
            }
            if ($id > 0) {
                $postData = array(
                    'appId' => $request->post('appId'),
                    'authCode' => $request->post('authCode'),
                    'secretKey' => $request->post('secretKey'),
                    'grantType' => 'auth_code',
                    'userId' => intval($request->post('userId')),
                );
                $jsonData = json_encode($postData);
                //使用临时授权(authCode)，通过换取授权令牌接接口
                $response = getAccessToken($jsonData);
                if (is_array($response) && count($response) > 0 && $response['code'] == 0) {
                    DB::table('baidu_xinxiliu_refreshToken')->insert([
                        'accessToken' => $response['data']['accessToken'],
                        'refreshToken' => $response['data']['refreshToken'],
                        'expiresTime' => $response['data']['expiresTime'],
                        'refreshExpiresTime' => $response['data']['refreshExpiresTime'],
                        'expiresIn' => $response['data']['expiresIn'],
                        'refreshExpiresIn' => $response['data']['refreshExpiresIn'],
                        'userId' => $response['data']['userId'],
                        'openId' => $response['data']['openId'],
                        'updated_at' => date("Y-m-d H:i:s", time()),
                        'created_at' => date("Y-m-d H:i:s", time()),
                    ]);
                    Log::info('insert___临时授权token换取 accessToken 和 refreshToken,userId:'.$response['data']['userId']."_____".date("Y-m-d H:i:s", time()).PHP_EOL);
                    //更新表里的 username字段
                    $postData = [
                        'openId' => $response['data']['openId'],
                        'accessToken' => $response['data']['accessToken'],
                        'userId' => $response['data']['userId'],
                        'needSubList' => true,
                        'pageSize' => 500,
                        'lastPageMaxUcId' => 1
                    ];
                    $jsonData = json_encode($postData);
                    $userInfo = getUserInfo($jsonData);
                    if (is_array($userInfo) && $userInfo['code'] == 0) {
                        DB::table('baidu_xinxiliu')->where(['userId' => $userInfo['data']['masterUid']])->update([
                            'userName' => $userInfo['data']['masterName'],
                        ]);
                        foreach ($userInfo['data']['subUserList'] as $subUser) {
                            if ($subUser['ucId'] == $response['data']['userId']) {
                                $user_payload = array(
                                    "header" => array(
                                        "userName" => $subUser['ucName'],
                                        "accessToken" => $response['data']['accessToken'],
                                        "action" => "API-PYTHON"
                                    ),
                                );
                                //信息流账户余额查询
                                $user_payload['body']=array(
                                    "accountFeedFields" => array(
                                        "userId",
                                        "balance",
                                        "budget",
                                        "balancePackage",
                                        "userStat",
                                        "uaStatus",
                                        "validFlows",
                                        "cid",
                                        "liceName",
                                        "tradeId",
                                        "budgetOfflineTime",
                                        "adtype"
                                    ));
                                $jsonData = json_encode($user_payload);
                                $AccountFeedData=getAccountFeed($jsonData);
                                if (is_array($AccountFeedData) && $AccountFeedData['header']['desc'] == 'success') {
                                    DB::table('baidu_xinxiliu')->where(['userId' => $subUser['ucId'] ])->update([
                                        'cid'=>$AccountFeedData['body']['data'][0]['cid'],
                                        'userName' =>$subUser['ucName'],
                                        'liceName'=>$AccountFeedData['body']['data'][0]['liceName'],
                                        'balance'=>$AccountFeedData['body']['data'][0]['balance'],
                                        'budget'=>$AccountFeedData['body']['data'][0]['budget'],
                                        'userStat'=>$AccountFeedData['body']['data'][0]['userStat'],
                                        'uaStatus'=>$AccountFeedData['body']['data'][0]['uaStatus'],
                                        'adtype'=>$AccountFeedData['body']['data'][0]['adtype'],
                                        'updated_at'=>date("Y-m-d H:i:s", time()),
                                    ]);
                                }
                            }
                        }
                        Log::info('更新 baidu_xinxiliu的字段 userName为' . $userInfo['data']['masterName']);
                    }
                }
                return view('index/view', ['name' => '授权成功']);
            }
        }
        return view('index/view', ['name' => '授权失败']);

    }


//通过超管账户更新下面的授权账户信息。
    function subUserList()
    {
        $baidu_xinxiliu_refreshToken = DB::table('baidu_xinxiliu')->where('status', '>', 0)
            ->leftJoin('baidu_xinxiliu_refreshToken', 'baidu_xinxiliu.userId', '=', 'baidu_xinxiliu_refreshToken.userId')
            ->where('baidu_xinxiliu.status', '>', 0)
            ->select('baidu_xinxiliu.userName', 'baidu_xinxiliu_refreshToken.*')
            ->get();
        foreach ($baidu_xinxiliu_refreshToken as $xinxiliu_refreshToken) {
            $postData = [
                'openId' => $xinxiliu_refreshToken->openId, //授权用户查询标识
                'accessToken' => $xinxiliu_refreshToken->accessToken, //已有的授权令牌
                'userId' => $xinxiliu_refreshToken->userId, //同意授权的推广账户ID
                'needSubList' => true, //是否需要子账号列表，值为true时返回subUserList
                'pageSize' => 500, //分页数量，默认100，最大不超过500
                'lastPageMaxUcId' => 1 //上一页返回的最大userid，用于子账号列表分页 查询子账号列表时，该字段为必填。第一次获取子账户列表时，该字段需要设置为1
            ];
            $jsonData = json_encode($postData);
            $userInfo = getUserInfo($jsonData);
            echo '账户信息' . PHP_EOL;
            var_dump($userInfo);
            if (is_array($userInfo) && $userInfo['code'] == 0) {
                foreach ($userInfo['data']['subUserList'] as $subUser) {
                    $user_payload = array(
                        "header" => array(
                            "userName" => $subUser['ucName'],
                            "accessToken" => $xinxiliu_refreshToken->accessToken,
                            "action" => "API-PYTHON"
                        ),
                    );
                    //信息流账户余额查询
                    $user_payload['body'] = array(
                        "accountFeedFields" => array(
                            "userId",
                            "balance",
                            "budget",
                            "balancePackage",
                            "userStat",
                            "uaStatus",
                            "validFlows",
                            "cid",
                            "liceName",
                            "tradeId",
                            "budgetOfflineTime",
                            "adtype"
                        ));
                    $jsonData = json_encode($user_payload);
                    $AccountFeedData = getAccountFeed($jsonData);
                    if (is_array($AccountFeedData) && $AccountFeedData['header']['desc'] == 'success') {
                        $updateId = DB::table('baidu_xinxiliu_subuser')
                            ->where('userId', $AccountFeedData['body']['data'][0]['userId'])
                            ->update([
                                'masterUid' => $userInfo['data']['masterUid'],
                                'masterName' => $userInfo['data']['masterName'],
                                'userId' => $AccountFeedData['body']['data'][0]['userId'],
                                'userName' => $subUser['ucName'],
                                'balancePackage' => $AccountFeedData['body']['data'][0]['balancePackage'],
                                'validFlows' => json_encode($AccountFeedData['body']['data'][0]['validFlows']),
                                'tradeId' => $AccountFeedData['body']['data'][0]['tradeId'],
                                'budgetOfflineTime' => json_encode($AccountFeedData['body']['data'][0]['budgetOfflineTime']),
                                'adminId' => 0,
                                'status' => 1,
                                'cid' => $AccountFeedData['body']['data'][0]['cid'],
                                'liceName' => $AccountFeedData['body']['data'][0]['liceName'],
                                'balance' => $AccountFeedData['body']['data'][0]['balance'],
                                'budget' => $AccountFeedData['body']['data'][0]['budget'],
                                'userStat' => $AccountFeedData['body']['data'][0]['userStat'],
                                'uaStatus' => $AccountFeedData['body']['data'][0]['uaStatus'],
                                'adtype' => $AccountFeedData['body']['data'][0]['adtype'],
                                'updated_at' => date("Y-m-d H:i:s", time()),
                            ]);
                        if ($updateId < 1) {
                            DB::table('baidu_xinxiliu_subuser')
                                ->insert([
                                    'masterUid' => $userInfo['data']['masterUid'],
                                    'masterName' => $userInfo['data']['masterName'],
                                    'userId' => $AccountFeedData['body']['data'][0]['userId'],
                                    'userName' => $subUser['ucName'],
                                    'balancePackage' => $AccountFeedData['body']['data'][0]['balancePackage'],
                                    'validFlows' => json_encode($AccountFeedData['body']['data'][0]['validFlows']),
                                    'tradeId' => $AccountFeedData['body']['data'][0]['tradeId'],
                                    'budgetOfflineTime' => json_encode($AccountFeedData['body']['data'][0]['budgetOfflineTime']),
                                    'adminId' => 0,
                                    'status' => 1,
                                    'cid' => $AccountFeedData['body']['data'][0]['cid'],
                                    'liceName' => $AccountFeedData['body']['data'][0]['liceName'],
                                    'balance' => $AccountFeedData['body']['data'][0]['balance'],
                                    'budget' => $AccountFeedData['body']['data'][0]['budget'],
                                    'userStat' => $AccountFeedData['body']['data'][0]['userStat'],
                                    'uaStatus' => $AccountFeedData['body']['data'][0]['uaStatus'],
                                    'adtype' => $AccountFeedData['body']['data'][0]['adtype'],
                                    'created_at' => date("Y-m-d H:i:s", time()),
                                    'updated_at' => date("Y-m-d H:i:s", time()),
                                ]);
                        }
                    }
                    echo $subUser['ucName'] . '信息流账户余额:' . PHP_EOL;
                    var_dump($AccountFeedData);
                }

            }
        }

    }

    function accountInfo()
    {
        $baidu_xinxiliu_refreshToken = DB::table('baidu_xinxiliu')->where('status', '>', 0)
            ->leftJoin('baidu_xinxiliu_refreshToken', 'baidu_xinxiliu.userId', '=', 'baidu_xinxiliu_refreshToken.userId')
            ->where('baidu_xinxiliu.status', '>', 0)
            ->select('baidu_xinxiliu.userName', 'baidu_xinxiliu_refreshToken.*')
            ->get();
        foreach ($baidu_xinxiliu_refreshToken as $xinxiliu_refreshToken) {
            //更新表里的 username字段
            if ($xinxiliu_refreshToken->userName == false) {
                $postData = [
                    'openId' => $xinxiliu_refreshToken->openId,
                    'accessToken' => $xinxiliu_refreshToken->accessToken,
                    'userId' => $xinxiliu_refreshToken->userId,
                    'needSubList' => true,
                    'pageSize' => 500,
                    'lastPageMaxUcId' => 1
                ];
                $jsonData = json_encode($postData);
                $userInfo = getUserInfo($jsonData);
                echo '账户信息' . PHP_EOL;
                var_dump($userInfo);
                if (is_array($userInfo) && $userInfo['code'] == 0) {
                    DB::table('baidu_xinxiliu')->where(['userId' => $userInfo['data']['masterUid']])->update([
                        'userName' => $userInfo['data']['masterName'],
                    ]);
                    foreach ($userInfo['data']['subUserList'] as $subUser) {
                        if ($subUser['ucId'] == $xinxiliu_refreshToken->userId) {
                            DB::table('baidu_xinxiliu')->where(['userId' => $xinxiliu_refreshToken->userId])->update([
                                'userName' => $userInfo['data']['masterName'],
                                'masterUid' => $userInfo['data']['masterUid'],
                                'masterName' => $userInfo['data']['masterName'],
                                'userAcctType' => $userInfo['data']['userAcctType'],
                                'hasNext' => $userInfo['data']['hasNext'] == true ? 1 : 0,
                                'updated_at' => date("Y-m-d H:i:s", time()),

                            ]);
                        }
                    }

                }
                $xinxiliu_refreshToken->userName = $userInfo['data']['masterName'];
            }
            $user_payload = array(
                "header" => array(
                    "userName" => $xinxiliu_refreshToken->userName,
                    "accessToken" => $xinxiliu_refreshToken->accessToken,
                    "action" => "API-PYTHON"
                ),
            );
            $user_payload['body'] = array(
                "accountFields" => array(
                    "userId",
                    "balance",
                    "pcBalance",
                    "budget",
                    "budgetType",
                    "budgetOfflineTime",
                    "cost",
                    "excludeIp",
                    "openDomains",
                    "payment",
                    "regDomain",
                    "regionTarget",
                    "userStat",
                    "userLevel",
                    "regionPriceFactor",
                    "queryRegionStatus",
                    "excludeQueryRegionStatus",
                    "textOptimizeSegmentStatus",
                    "sysLongLinkSegmentStatus",
                    "longMonitorSublink",
                    "accountMonitorUrl",
                    "cid"
                )
            );

            $jsonData = json_encode($user_payload);
            $accountData = getAccountInfo($jsonData);
            $user_payload['body'] = array(
                "productIds" => array(
                    1,
                ));
            $jsonData = json_encode($user_payload);
            $balanceData = getBalanceInfo($jsonData);
            echo '财务管理--查询账户余额成分:' . PHP_EOL;
            var_dump($balanceData);
            //信息流账户余额查询
            $user_payload['body'] = array(
                "accountFeedFields" => array(
                    "userId",
                    "balance",
                    "budget",
                    "balancePackage",
                    "userStat",
                    "uaStatus",
                    "validFlows",
                    "cid",
                    "liceName",
                    "tradeId",
                    "budgetOfflineTime",
                    "adtype"
                ));
            $jsonData = json_encode($user_payload);
            $AccountFeedData = getAccountFeed($jsonData);
            if (is_array($AccountFeedData) && $AccountFeedData['header']['desc'] == 'success') {
                DB::table('baidu_xinxiliu')->where(['userId' => $xinxiliu_refreshToken->userId])->update([
                    'cid' => $AccountFeedData['body']['data'][0]['cid'],
                    'liceName' => $AccountFeedData['body']['data'][0]['liceName'],
                    'balance' => $AccountFeedData['body']['data'][0]['balance'],
                    'budget' => $AccountFeedData['body']['data'][0]['budget'],
                    'userStat' => $AccountFeedData['body']['data'][0]['userStat'],
                    'uaStatus' => $AccountFeedData['body']['data'][0]['uaStatus'],
                    'adtype' => $AccountFeedData['body']['data'][0]['adtype'],
                    'updated_at' => date("Y-m-d H:i:s", time()),
                ]);
            }
            echo '信息流账户余额:' . PHP_EOL;
            var_dump($AccountFeedData);
        }
        return json(['code' => 0, 'msg' => $AccountFeedData]);

    }

}