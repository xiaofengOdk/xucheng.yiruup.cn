<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../support/bootstrap.php';

use support\Db;
//抓取河南预约挂号平台医院数据
//https://www.169000.net/guahao/index.html#/anhos

// 初始化 cURL 会话
$ch = curl_init();

for ($i = 1; $i <= 32; $i++) {
    echo $i . PHP_EOL;
    $url = 'https://www.169000.net/api/search/listHos?hasHao=&type=0&precise=false&location=&pageNum=' . $i . '&pageSize=10&city=&countyId=&aiSort=&hosLevel=&hosType=&hosNature=&hosService=&serviceDate=';

// 设置请求的 URL

    curl_setopt($ch, CURLOPT_URL, $url);

// 设置请求头
    $headers = [
        'Accept: */*',
        'Accept-Language: zh-CN,zh;q=0.9',
        'Connection: keep-alive',
        'DNT: 1',
        'Referer: https://www.169000.net/guahao/index.html',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: same-origin',
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
        'channelId: FTSK20240718MP0bN1gI1KvYZb8dM8V5',
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
    foreach($data['data']['list'] as $key => $data1)
     Db::table('hospital_169000')->insert($data1);
}
