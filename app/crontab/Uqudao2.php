<?php

namespace app\crontab;

use support\Log;

// 获取当前时间的小时数（24小时制）
$currentHour = (int)date('H');
// 判断是否在晚上11点（23点）后到早上6点（6点）之前
if ($currentHour >= 24 || $currentHour < 5) {
    Log::channel('uqudao')->info("当前时间在晚上11点到早上6点之间，停止运行。");
    exit; // 退出程序
}
$ids = [295211, 298711, 296555, 294877, 292349];
foreach ($ids as $id) {
    $url = 'https://www.uqudao.com/my/refresh?id=' . $id;

    $headers = [
        'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'accept-language: zh-CN,zh;q=0.9',
        'priority: u=0, i',
        'sec-ch-ua: "Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "macOS"',
        'sec-fetch-dest: document',
        'sec-fetch-mode: navigate',
        'sec-fetch-site: none',
        'sec-fetch-user: ?1',
        'upgrade-insecure-requests: 1',
        'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 返回响应内容
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // 设置头部信息

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        echo $response; // 输出响应内容
    }

    curl_close($ch);
    Log::channel('uqudao')->info('https://www.uqudao.com/detail/' . $id . '____' . $response);
    $seconds = rand(180, 360);
    sleep($seconds);
}
