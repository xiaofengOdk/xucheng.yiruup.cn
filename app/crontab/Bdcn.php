<?php

namespace app\crontab;

// 获取当前时间的小时数（24小时制）
$currentHour = (int)date('H');
// 判断是否在晚上11点（23点）后到早上6点（6点）之前
if ($currentHour >= 23 || $currentHour < 6) {
    //Log::channel('uqudao')->info( "当前时间在晚上11点到早上6点之间，停止运行。");
    exit; // 退出程序
}

$url = 'https://www.bd.cn/yh/login';

$headers = [
    'accept: */*',
    'accept-language: zh-CN,zh;q=0.9',
    'content-type: application/x-www-form-urlencoded; charset=UTF-8',
    'cookie: PHPSESSID=js91snrg35mspvjf4rftma58pk; jm_language=zh-CN; Hm_lvt_571cd7167b397af7a45a84a08b009b3c=1734944729; HMACCOUNT=BA558F3FFF4CBF9F; jlqun=666; acw_tc=0b63bb3217349474986827323e02f0668703b372bcd5eee0b6c98a26df4818; Hm_lpvt_571cd7167b397af7a45a84a08b009b3c=1734947501; SERVERID=f02c1e3c94cd3af7991f1700c488e211|1734947513|1734947498',
    'dnt: 1',
    'origin: https://www.bd.cn',
    'priority: u=1, i',
    'referer: https://www.bd.cn/login',
    'sec-ch-ua: "Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "macOS"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-origin',
    'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    'x-requested-with: XMLHttpRequest',
];

$data = [
    'lx' => '1',
    'sjh' => '13146078519',
    'mm' => 'Dfadacai123',
    'p_yzm' => '9795',
    'remember' => '1',
];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 返回响应内容
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // 设置头部信息
curl_setopt($ch, CURLOPT_POST, true); // 设置为 POST 请求
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // 发送 POST 数据

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    echo $response; // 输出响应内容
}

curl_close($ch);