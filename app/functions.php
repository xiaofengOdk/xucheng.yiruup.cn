<?php

use support\Db;
use support\Log;
//微信机器人，帮助文档 https://developer.work.weixin.qq.com/document/path/91770
function push_work_weixin($key = '4742b357-8c97-4f7c-9702-86c7986cdf9c', $message = '', $mentioned_list = ["@all"], $msgtype = 'markdown')
{
    $url = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=' . $key;
    switch ($msgtype) {
        case 'text':
            $work_message = [
                'msgtype' => 'text',
                'text' => [
                    'content' => $message,
                    "mentioned_list" => $mentioned_list,
                ]
            ];
            break;
        case 'markdown':
            $work_message = [
                'msgtype' => 'markdown',
                'markdown' => [
                    'content' => $message,
                    "mentioned_list" => $mentioned_list,
                ]
            ];
            break;
        default:
            $work_message = [];
    }
    // 将数据编码为 JSON
    $jsonData = json_encode($work_message, JSON_UNESCAPED_UNICODE);

    // 初始化 cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    // 设置超时时间（秒）
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 连接超时
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 响应超时

    // 执行请求
    $response = curl_exec($ch);

    // 错误处理
    if (curl_errno($ch)) {
        Log::channel('workweixin')->error('企微推送失败' . curl_error($ch));
    } else {
        Log::channel('workweixin')->info('企微推送' . $response);
    }
    // 关闭连接
    curl_close($ch);
    return json_decode($response, true);
}

function mcrypt_encode($string, $expiry = 0)
{
    $key = config('app.mcrypt_key');
    $ckeyLength = 4;
    $key = md5($key); //解密密匙
    $keya = md5(substr($key, 0, 16));         //做数据完整性验证
    $keyb = md5(substr($key, 16, 16));         //用于变化生成的密文 (初始化向量IV)
    $keyc = substr(md5(microtime()), -$ckeyLength);
    $cryptkey = $keya . md5($keya . $keyc);
    $keyLength = strlen($cryptkey);
    $string = sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $stringLength = strlen($string);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $keyLength]);
    }

    $box = range(0, 255);
    // 打乱密匙簿，增加随机性
    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 加解密，从密匙簿得出密匙进行异或，再转成字符
    $result = '';
    for ($a = $j = $i = 0; $i < $stringLength; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    $result = $keyc . str_replace('=', '', base64_encode($result));
    $result = str_replace(array('+', '/', '='), array('-', '_', '.'), $result);
    return $result;
}

/**
 * 字符解密，一次一密,可定时解密有效
 * @param string $string 密文
 * @param string $key 解密密钥
 * @return string 解密后的内容
 */
function mcrypt_decode($string)
{
    $key = config('app.mcrypt_key');
    $string = str_replace(array('-', '_', '.'), array('+', '/', '='), $string);
    $ckeyLength = 4;
    $key = md5($key); //解密密匙
    $keya = md5(substr($key, 0, 16));         //做数据完整性验证
    $keyb = md5(substr($key, 16, 16));         //用于变化生成的密文 (初始化向量IV)
    $keyc = substr($string, 0, $ckeyLength);
    $cryptkey = $keya . md5($keya . $keyc);
    $keyLength = strlen($cryptkey);
    $string = base64_decode(substr($string, $ckeyLength));
    $stringLength = strlen($string);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $keyLength]);
    }

    $box = range(0, 255);
    // 打乱密匙簿，增加随机性
    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 加解密，从密匙簿得出密匙进行异或，再转成字符
    $result = '';
    for ($a = $j = $i = 0; $i < $stringLength; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0)
        && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)
    ) {
        return substr($result, 26);
    } else {
        return '';
    }
}

/*
 * 获取授权令牌：使用临时授权(authCode)，通过换取授权令牌接接口（https://u.baidu.com/oauth/accessToken ）可获取授权令牌（accessToken）和刷新令牌（refreshToken）
 * 授权令牌的有效时间默认为：86400秒=24小时；更新令牌的有效时间默认为：2592000秒=30天；
 */
function getAccessToken($data)
{
    //换取令牌接口
    $accessTokenUrl = 'https://u.baidu.com/oauth/accessToken';
    $response = getData($accessTokenUrl, $data);
    $response = json_decode($response, true);
    return $response;
}

function getRefreshToken($data)
{
    //更新授权令牌接口
    $refreshTokenUrl = 'https://u.baidu.com/oauth/refreshToken';
    $response = getData($refreshTokenUrl, $data);
    $response = json_decode($response, true);
    return $response;
}

function getReportData($data)
{
    //一站式多渠道报告
    $refreshTokenUrl = 'https://api.baidu.com/json/sms/service/OpenApiReportService/getReportData';
    $response = getData($refreshTokenUrl, $data);
    $response = json_decode($response, true);
    return $response;
}

function getVideoLabelData($data)
{
    //获取视频内容见解数据
    $refreshTokenUrl = 'https://api.baidu.com/json/sms/service/VideoDataService/getLabelData';
    $response = getData($refreshTokenUrl, $data);
    $response = json_decode($response, true);
    return $response;
}

//
function getUserInfo($data)
{
    //3.4.3 查询授权用户信息
    $getUserInfoUrl = 'https://u.baidu.com/oauth/getUserInfo';
    $response = getData($getUserInfoUrl, $data);
    $response = json_decode($response, true);
    return $response;

}

//是搜索广告的，暂时用不上。
function getAccountInfo($data)
{
    //获取账户信息
    $getAccountInfoUrl = 'https://api.baidu.com/json/sms/service/AccountService/getAccountInfo';
    $response = getData($getAccountInfoUrl, $data);
    $response = json_decode($response, true);
    return $response;
}

function getBalanceInfo($data)
{
    //查询账户余额成分
    $getBalanceInfoUrl = 'https://api.baidu.com/json/sms/service/BalanceService/getBalanceInfo';
    $response = getData($getBalanceInfoUrl, $data);
    $response = json_decode($response, true);
    return $response;
}

//信息流查询账户信息
function getAccountFeed($data)
{
    $getAccountFeedUrl = 'https://api.baidu.com/json/feed/v1/AccountFeedService/getAccountFeed';
    $response = getData($getAccountFeedUrl, $data);
    $response = json_decode($response, true);
    return $response;
}
//查询计划
function getCampaignFeed($data){
    $url='https://api.baidu.com/json/feed/v1/CampaignFeedService/getCampaignFeed';
    $response = getData($url, $data);
    $response = json_decode($response, true);
    return $response;
}
function getData($url, $jsonData)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json;charset=utf-8',
    ));
    //10秒超时
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    $response = curl_exec($ch);

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
function truncateString($str, $maxLength = 100) {
    $length = 0;
    $result = '';
    $strLength = mb_strlen($str, 'UTF-8');

    for ($i = 0; $i < $strLength; $i++) {
        $char = mb_substr($str, $i, 1, 'UTF-8');
        // 判断字符是否为中文
        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $char)) {
            $charLength = 2;
        } else {
            $charLength = 1;
        }

        if ($length + $charLength <= $maxLength) {
            $result .= $char;
            $length += $charLength;
        } else {
            break;
        }
    }

    return $result;
}

function  writelog($response,$getupd){
    $logDir = runtime_path() . 'errlog/' . date('Y-m-d') .$getupd.'.log';

    // 确保日志目录存在
    if (!is_dir(dirname($logDir))) {
        mkdir(dirname($logDir), 0777, true);
    }

    // 打开/创建日志文件并追加内容
    $file = fopen($logDir, 'a');
    if ($file) {
        // 写入日期和时间戳（可选）
        fwrite($file, date('[Y-m-d H:i:s]') . " - ");

        // 写入响应结果（假设已转换为字符串）
        fwrite($file, $response . "\n");

        // 写入横线作为分隔符
        fwrite($file, "---------------------------------\n");

        // 关闭文件
        fclose($file);
    } else {
        // 处理文件打开失败的情况（例如，记录错误到另一个日志或抛出异常）
    }

}

/**
 * 检查API响应是否成功
 */
function isApiResponseSuccess(array $result): bool
{
    return isset($result['header']['desc']) && $result['header']['desc'] === 'success';
}

/**
 * 从API响应中提取campaignFeedId
 */
function extractCampaignFeedIds(array $result): array
{
    $campaignFeedIds = [];

    if (isset($result['body']['data']) && is_array($result['body']['data'])) {
        foreach ($result['body']['data'] as $item) {
            $campaignFeedIds[] = $item['campaignFeedId'];
        }
    }

    return $campaignFeedIds;
}

/**
 * 获取API错误消息
 */
function getApiErrorMessage(array $result): string
{
    if (isset($result['header']['failures'][0]['message'])) {
        return $result['header']['failures'][0]['message'];
    } elseif (isset($result['header']['desc'])) {
        return $result['header']['desc'];
    }

    return '未知错误';
}
//
function detectPhoneBrand($userAgent) {
    $ua = strtolower($userAgent);
    if (preg_match('/huawei/i', $ua)) return '荣耀手机';
    if (preg_match('/honor/i', $ua)) return '荣耀手机';
    if (preg_match('/; m/i', $ua)) return '小米手机';
    if (preg_match('/; 2/i', $ua)) return '小米手机';
    if (preg_match('/redmi/i', $ua)) return '小米手机';
    if (preg_match('/; t/i', $ua)) return '华为手机';
    if (preg_match('/; sm/i', $ua)) return '三星手机';
    if (preg_match('/; v/i', $ua)) return 'VIVO手机';
    if (preg_match('/; p/i', $ua)) return 'OPPO手机';

    return '百度';
}