<?php
//有刷量 假量的 微信通知 5分钟运行一次计划任务 每次拉去半小时以内的数据
namespace app\crontab;

use support\Db;
use support\Redis;

// 记录开始时间
$startTime = microtime(true);

$time_before_30_min = date('Y-m-d H:i:s', strtotime('-5 minutes'));
//只查询比当前时间提前半小时的数据
Db::table('ad_brush')->where('created_at', '>', $time_before_30_min)->orderBy('id', 'desc')->chunkById(500, function ($data) {
    $url = "https://ocpc.baidu.com/ocpcapi/api/uploadConvertData";
    $token_map = [
        'J' => 'ZPUh89zVs3kVBOby423phCrS4V7LviQS@igzZ2qxh1JjIKzwBpxDwteicUkxqbHfR', //江西端口
        'R' => 'SKsHVFenNsD8jyGPWRftPC3YVzz25o5z@8jYk2EGgEovI4IG93Gpk0NC7TvTIbDcI',//锐旗端口
        'Q' => 'ab92VlwgTWK2FxV3usqNV65deJMh5mCw@X8AmZ7c8UyHMdt9vpa9V4fbftWTtNEky',  //青岛端口
    ];
    $subuser_map = [];
    $logidUrl_map = [];
    $dataById_map = [];
    foreach ($data as $ad) {
        // if (ctype_digit($ad->bd_vid)) {
        $dataById_map[$ad->id] = $ad;
        if (isset($ad->subuser) && $ad->subuser != '') {
            if (isset($subuser_map[$ad->subuser])) {
                $subuser_map[$ad->subuser]++;
            } else
                $subuser_map[$ad->subuser] = 1;
            $logidUrl_map[$ad->subuser][$ad->id] = $ad->url . '?bd_vid=' . $ad->bd_vid . '&1=1';
        }
        // }
    }
    if (count($subuser_map) > 0) {
        foreach ($subuser_map as $subuser => $count) {
            if ($count > 1) {
                //是否提醒过
                $is_push = Redis::ttl('weixin_shualiang_' . $subuser);
                if ($is_push < 0) {
                    $project = Db::table('baidu_xinxiliu_project')->select('clientName')->where('subName', $subuser)->first();

                    $weixin_message = "信息流账户：<font color=\"info\">" . $subuser . "</font> 可能存在刷量现象。
                     >5分钟内请求数量为<font color=\"comment\">" . $count . "次</font>。
                     >请<font color=\"info\">相关同事排查</font>。";
                    if (isset($project->clientName)&&$project->clientName!="")
                        $weixin_message .= ">项目：<font color=\"comment\">" . $project->clientName . "</font>。";
                    $weixin_message.=">时间:" . date("Y - m - d H:i:s") . "。\n";
                    $xinxiliu_subuser = Db::table('baidu_xinxiliu_subuser')->select('adminId')->where('userName', $subuser)->first();
                    if (isset($xinxiliu_subuser->adminId)&& $xinxiliu_subuser->adminId > 0) {
                        $youhuashi = Db::table('wa_admins')->select('username','weixin')->where('id', $xinxiliu_subuser->adminId)->first();
                        $weixin_message .= " > 优化师: <font color = \"info\">" . $youhuashi->username . "</font>。\n";
                        if ($youhuashi->weixin != null) $weixin_message .= "<@" . $youhuashi->weixin . ">";
                    }
                //微信通知
                push_work_weixin('1a3e4c28-40e5-4937-bfb6-0157ccbd3b5b', $weixin_message);
                //只提醒一次 缓存十分钟
                Redis::set('weixin_shualiang_' . $subuser, $count);
                Redis::expire('weixin_shualiang_' . $subuser, 300);
            }
        }
        //给百度提交刷量参数
        foreach ($logidUrl_map[$subuser] as $k => $_l) {
            if ($dataById_map[$k]->ispush == 0 && isset($token_map[$subuser[0]])) {
                $post_data = [
                    "token" => $token_map[$subuser[0]],
                    "conversionTypes" => [
                        [
                            "logidUrl" => $_l,
                            "newType" => 56
                        ],
                    ]
                ];
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 连接超时时间（秒）
                curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 请求超时时间（秒）
                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                    echo "cURL 错误: " . curl_error($ch);
                }
                curl_close($ch);

                var_dump($response);
                Db::table('ad_brush')
                    ->where('id', $k)
                    ->update(['ispush' => 1]);
            }
        }
    }
}
});