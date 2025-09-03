<?php
require_once __DIR__ . '/vendor/autoload.php';
use support\Db;
Db::table('baidu_xinxiliu')->orderBy('id')->chunkById(200, function ($xinxiliu_db) {
    foreach ($xinxiliu_db as $xinxiliu) {
        var_dump($xinxiliu);
    }
});

exit;
$secretKey = "bdec73bab81d0ff3d30b81028a94c497";
$appId = "32f53713d4a17829ed293cfb95980166";
$authCode = 'eyJhbGciOiJIUzM4NCJ9.eyJhdWQiOiJxaW5ncWluZyIsInN1YiI6ImV4YyIsInVpZCI6NTUyNjgxMTQsImFwcElkIjoiMzJmNTM3MTNkNGExNzgyOWVkMjkzY2ZiOTU5ODAxNjYiLCJpc3MiOiLllYbkuJrlvIDlj5HogIXkuK3lv4MiLCJwbGF0Zm9ybUlkIjoiNDk2MDM0NTk2NTk1ODU2MTc5NCIsImV4cCI6MTcxNjA0NzI4NCwianRpIjoiODQ3MTc5MzQwNzYzNDE5NDQ4NiJ9.5CydyWd-3r0lM--efQ57KGV8ysnWYs_jDv1mjB7Vra7BrM6CzebqhF5sIkYc_b-0';
$userId = 55268114;
$grantType = 'auth_code';
$accessTokenUrl = 'https://u.baidu.com/oauth/accessToken';
$getUserInfoUrl = 'https://u.baidu.com/oauth/getUserInfo';
//获取账户信息
$getAccountInfoUrl='https://api.baidu.com/json/sms/service/AccountService/getAccountInfo';
//查询账户余额成分
$getBalanceInfoUrl='https://api.baidu.com/json/sms/service/BalanceService/getBalanceInfo';
//信息流查询账户信息
$getAccountFeedUrl='https://api.baidu.com/json/feed/v1/AccountFeedService/getAccountFeed';
$postData = array(
    'appId' => $appId,
    'authCode' => $authCode,
    'secretKey' => $secretKey,
    'grantType' => $grantType,
    'userId' => $userId,
);
$jsonData = json_encode($postData);
/*
$response = accessToken($accessTokenUrl, $jsonData);
$response=json_decode($response, true);
var_dump($response);

$postData = [
    'openId' => $response['openId'],
    'accessToken' => $response['accessToken'],
    'userId' => $response['userId'],
    'needSubList' => true,
    'pageSize' => 500,
    'lastPageMaxUcId' => 1
];
*/
$accessToken='eyJhbGciOiJIUzM4NCJ9.eyJzdWIiOiJhY2MiLCJhdWQiOiJxaW5ncWluZyIsInVpZCI6NTUyNjgxMTQsImFwcElkIjoiMzJmNTM3MTNkNGExNzgyOWVkMjkzY2ZiOTU5ODAxNjYiLCJpc3MiOiLllYbkuJrlvIDlj5HogIXkuK3lv4MiLCJwbGF0Zm9ybUlkIjoiNDk2MDM0NTk2NTk1ODU2MTc5NCIsImV4cCI6MTcxNjEzMjA5OSwianRpIjoiODIyNzE2MjkyODEzMTE5NDg5MSJ9.nWq74Ggqhuvm8dPA2_Zk1JPanH2gVUtIfHSCOMXg3MUf4W8iLkHBs4Qyb5_caa77';
$postData = [
    'openId' => 8601025984273645599,
    'accessToken' => $accessToken,
    'userId' => 55268114,
    'needSubList' => true,
    'pageSize' => 500,
    'lastPageMaxUcId' => 1
];
$jsonData = json_encode($postData);

$userInfo = getUserInfo($getUserInfoUrl, $jsonData);
$userInfo=json_decode($userInfo, true);
var_dump($userInfo);
// 准备请求的 JSON 数据
$user_payload = array(
    "header" => array(
        "userName" => $userInfo['data']['masterName'],
        "accessToken" => $accessToken,
        "action" => "API-PYTHON"
    ),
    "body" => array(
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
    )
);

$jsonData = json_encode($user_payload);
$accountData=getAccountInfo($getAccountInfoUrl,$jsonData);
$accountData=json_decode($accountData, true);
var_dump($accountData);
$user_payload['body']=array(
    "productIds" => array(
        1,
        502
    ));
$jsonData = json_encode($user_payload);
$balanceData=getBalanceInfo($getBalanceInfoUrl,$jsonData);
$balanceData=json_decode($balanceData, true);
var_dump($balanceData);
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
$AccountFeedData=getAccountFeed($getAccountFeedUrl,$jsonData);

$AccountFeedData=json_decode($AccountFeedData, true);
var_dump($AccountFeedData);
function accessToken($url, $data)
{
    $response = getData($url, $data);

    return $response;
}
//查询账户余额成分
function getBalanceInfo($url, $data)
{
    $response = getData($url, $data);

    return $response;
}
//查询授权用户信息
function getUserInfo($url, $data)
{
    $response = getData($url, $data);

    return $response;
}
//信息流查询账户信息
function getAccountFeed($url, $data)
{
    $response = getData($url, $data);

    return $response;
}
function getAccountInfo($url,$data)
{
    $response = getData($url, $data);

    return $response;
}
function getData($url, $jsonData)
{
    // 创建一个 cURL 会话
    $ch = curl_init();

    // 设置请求的 URL
    curl_setopt($ch, CURLOPT_URL, $url);

    // 设置请求头，指定 Content-Type
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json;charset=utf-8',
    ));

    // 设置 cURL 选项，返回获取的输出而不是直接输出
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // 设置 cURL 选项，使用 POST 方法
    curl_setopt($ch, CURLOPT_POST, true);
    // 设置发送的 POST 字符
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    // 执行 cURL 请求
    $response = curl_exec($ch);

    // 检查是否有错误发生
    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
    } else {
        // 获取信息并输出
        $info = curl_getinfo($ch);
        //echo 'HTTP Status Code: ' . $info['http_code'] . "\n";
        // 输出响应内容
        //echo 'Response: ' . $response;
    }
    // 关闭 cURL 会话
    curl_close($ch);
    // 返回响应内容
    return $response;
}