<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../support/bootstrap.php';

use support\Db;
// 初始化 cURL 会话
$ch = curl_init();

// 设置请求的 URL
$url = 'https://www.169000.net/fastGh/1';
curl_setopt($ch, CURLOPT_URL, $url);

// 设置请求方法为 POST
curl_setopt($ch, CURLOPT_POST, true);

// 设置请求头
$headers = [
    'Accept: */*',
    'Accept-Language: zh-CN,zh;q=0.9',
    'Connection: keep-alive',
    'Content-Length: 0',
    'DNT: 1',
    'Origin: https://www.169000.net',
    'Referer: https://www.169000.net/',
    'Sec-Fetch-Dest: empty',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: same-origin',
    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
    'X-Requested-With: XMLHttpRequest',
    'sec-ch-ua: "Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "macOS"'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// 设置返回响应而不是直接输出
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// 执行 cURL 请求
$response = curl_exec($ch);

// 检查是否有错误
if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch);
}

// 关闭 cURL 会话
curl_close($ch);

// 输出响应
$data=json_decode($response,true);
$city=[];
foreach($data['data'] as $data1){
$city[$data1['id']]=$data1['name'];
}
var_dump($city);
$data=Db::table('hospital_169000')->select()->get()->toArray();
foreach($data as $data1){
    Db::table('hospital_169000')
        ->where('city',$data1->city)
        ->update(['city_name' => $city[$data1->city]]);
}

