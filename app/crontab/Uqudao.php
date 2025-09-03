<?php

namespace app\crontab;

use support\Log;

$url = 'https://www.uqudao.com/loginAjax';

$headers = [
    'accept: application/json, text/javascript, */*; q=0.01',
    'accept-language: zh-CN,zh;q=0.9',
    'content-type: application/x-www-form-urlencoded; charset=UTF-8',
    'cookie: Hm_lvt_4c5f6d4c6c18884939a731ca8b130de9=1734434670; HMACCOUNT=D1D3D829A80B9E71; Hm_lpvt_4c5f6d4c6c18884939a731ca8b130de9=1734444754',
    'origin: https://www.uqudao.com',
    'priority: u=0, i',
    'referer: https://www.uqudao.com/',
    'sec-ch-ua: "Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "macOS"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-origin',
    'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    'x-request-type: 1',
    'x-requested-with: XMLHttpRequest',
];

$data = http_build_query([
    'mobile' => '17600185706',
    'password' => '54kele45',
]);
$data = http_build_query([
    'mobile' => '13146078519',
    'password' => 'dashazi123',
]);


$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
//curl_setopt($ch, CURLOPT_HEADER, 1); // 将头文件的信息作为数据流输出
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$cookie = '';
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    //preg_match('/Set-Cookie:(.*);/iU', $response, $str); // 正则匹配 COOKIE
    $response = json_decode($response, true);
    //var_dump($response);
    // 打印正文内容
    // echo "Response Body:\n" . $cookie . "\n";
    $cookie = $response['data'];

}
curl_close($ch);
if ($cookie != '') {

    $url = 'https://www.uqudao.com/my/release';

    $headers = [
        'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'accept-language: zh-CN,zh;q=0.9',
        'cookie: Hm_lvt_4c5f6d4c6c18884939a731ca8b130de9=1734434670; HMACCOUNT=D1D3D829A80B9E71; W-UQ-Token=' . $cookie . '; Hm_lpvt_4c5f6d4c6c18884939a731ca8b130de9=1734449179',
        'priority: u=0, i',
        'referer: https://www.uqudao.com/my/card',
        'sec-ch-ua: "Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "macOS"',
        'sec-fetch-dest: document',
        'sec-fetch-mode: navigate',
        'sec-fetch-site: same-origin',
        'sec-fetch-user: ?1',
        'upgrade-insecure-requests: 1',
        'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 确保返回响应内容
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    /*-----使用 COOKIE-----*/
    //curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        //echo $response;
    }

    curl_close($ch);

    $id = [295211, 298711, 296555, 294877, 292349];
    $random_key = array_rand($id);
    $id = $id[$random_key];


// 初始化 cURL 会话
    $curl = curl_init();

// 设置 cURL 选项
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://www.uqudao.com/my/refresh?id=" . $id, // 目标 URL
        CURLOPT_RETURNTRANSFER => true, // 返回结果而不是直接输出
        CURLOPT_ENCODING => "", // 自动解码
        CURLOPT_MAXREDIRS => 10, // 允许的最大重定向次数
        CURLOPT_TIMEOUT => 30, // 超时时间
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // 使用 HTTP/1.1
        CURLOPT_CUSTOMREQUEST => "GET", // 请求方式
        CURLOPT_HTTPHEADER => array(
            "accept: application/json, text/javascript, */*; q=0.01",
            "accept-language: zh-CN,zh;q=0.9",
            "cookie: Hm_lvt_4c5f6d4c6c18884939a731ca8b130de9=1734434670; HMACCOUNT=D1D3D829A80B9E71; W-UQ-Token=" . $cookie,
            "priority: u=1, i",
            "referer: https://www.uqudao.com/my/release",
            "sec-ch-ua: \"Google Chrome\";v=\"131\", \"Chromium\";v=\"131\", \"Not_A Brand\";v=\"24\"",
            "sec-ch-ua-mobile: ?0",
            "sec-ch-ua-platform: \"macOS\"",
            "sec-fetch-dest: empty",
            "sec-fetch-mode: cors",
            "sec-fetch-site: same-origin",
            "user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
            "x-request-type: 1",
            "x-requested-with: XMLHttpRequest",
            "x-uq-web-token: " . $cookie,
        ),
    ));

// 执行 cURL 请求
    $response = curl_exec($curl);

// 检查是否有错误发生
    if (curl_errno($curl)) {
        echo 'Error:' . curl_error($curl);
    }

// 关闭 cURL 会话
    curl_close($curl);

// 输出响应结果
    echo $response;
    Log::channel('uqudao')->info('https://www.uqudao.com/my/refresh?id=' . $id . '____' . $response);
}
