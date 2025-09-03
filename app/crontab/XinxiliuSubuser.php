<?php
//信息流账户余额查询 每4分钟执行一次 更新baidu_xinxiliu_subuser表里的数据
namespace app\crontab;

use support\Db;
use support\Log;
use support\Redis;

// 记录开始时间
$startTime = microtime(true);
$baidu_xinxiliu_refreshToken = DB::table('baidu_xinxiliu')
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
    //echo '账户信息' . PHP_EOL;
    //var_dump($userInfo);
    if (is_array($userInfo) && $userInfo['code'] == 0) {
        $is_exist = Db::table('baidu_xinxiliu_subuser')
            ->where('userId', $userInfo['data']['masterUid'])
            ->exists();

        if ($is_exist) {
            DB::table('baidu_xinxiliu_subuser')
                ->where('userId', $userInfo['data']['masterUid'])
                ->update([
                    'masterUid' => $userInfo['data']['masterUid'],
                    'masterName' => $userInfo['data']['masterName'],
                    'updated_at' => date("Y-m-d H:i:s", time()),
                ]);
        } else {
            DB::table('baidu_xinxiliu_subuser')
                ->insert([
                    'masterUid' => $userInfo['data']['masterUid'],
                    'masterName' => $userInfo['data']['masterName'],
                    'userId' => $userInfo['data']['masterUid'],
                    'userName' => $userInfo['data']['masterName'],
                    'balance' => 0,
                    'budgetOfflineTime' => '',
                    'liceName' => '',
                    'created_at' => date("Y-m-d H:i:s", time()),
                    'updated_at' => date("Y-m-d H:i:s", time()),
                ]);
            Redis::set('weixin_push_0is_' . $userInfo['data']['masterUid'], 1);
            Redis::set('weixin_push_60is_' . $userInfo['data']['masterUid'], 1);
        }
        foreach ($userInfo['data']['subUserList'] as $subUser) {
            $is_exist = Db::table('baidu_xinxiliu_subuser')
                ->where('userId', $subUser['ucId'])
                ->exists();
            if ($is_exist) {
                DB::table('baidu_xinxiliu_subuser')
                    ->where('userId', $subUser['ucId'])
                    ->update([
                        'masterUid' => $userInfo['data']['masterUid'],
                        'masterName' => $userInfo['data']['masterName'],
                        'updated_at' => date("Y-m-d H:i:s", time()),
                    ]);
            } else {
                DB::table('baidu_xinxiliu_subuser')
                    ->insert([
                        'masterUid' => $userInfo['data']['masterUid'],
                        'masterName' => $userInfo['data']['masterName'],
                        'userId' => $subUser['ucId'],
                        'userName' => $subUser['ucName'],
                        'balance' => 0,
                        'budgetOfflineTime' => '',
                        'liceName' => '',
                        'created_at' => date("Y-m-d H:i:s", time()),
                        'updated_at' => date("Y-m-d H:i:s", time()),
                    ]);
                Redis::set('weixin_push_0is_' . $subUser['ucId'], 1);
                Redis::set('weixin_push_60is_' . $subUser['ucId'], 1);
            }
        }

    }
}
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
Log::channel('crontab')->info('更新subuser信息流账户余额 完毕,XinxiliuSubuser.php 代码运行时间: ' . $executionTime . "秒");