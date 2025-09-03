<?php
//因为accessToken过期时间是1天，refreshToken过期时间是30天 所以当accessToken还有2小时过期的时候 用refreshToken刷新accessToken 每30分钟执行一次
namespace app\crontab;

use support\Db;
use support\Log;

// 记录开始时间
$startTime = microtime(true);
Db::table('baidu_xinxiliu_refreshToken')->orderBy('id')->chunkById(200, function ($xinxiliu_refreshToken) {
    foreach ($xinxiliu_refreshToken as $refreshToken) {
        //距离过期还有两小时的时候 刷新token
        if ((strtotime($refreshToken->expiresTime) - time()) < 7200) {
            $xinxiliu = DB::table('baidu_xinxiliu')->where('userId', $refreshToken->userId)->first();
            $postData = array(
                'appId' => $xinxiliu->appId,
                'refreshToken' => $refreshToken->refreshToken,
                'secretKey' => $xinxiliu->secretKey,
                'userId' => $refreshToken->userId,
            );
            $jsonData = json_encode($postData);
            //使用临时授权(authCode)，通过换取授权令牌接接口
            $response = getRefreshToken($jsonData);
            if (is_array($response) && count($response) > 0 && $response['code'] == 0) {
                DB::table('baidu_xinxiliu_refreshToken')->where(['id' => $refreshToken->id])->update([
                    'accessToken' => $response['data']['accessToken'],
                    'refreshToken' => $response['data']['refreshToken'],
                    'expiresTime' => $response['data']['expiresTime'],
                    'refreshExpiresTime' => $response['data']['refreshExpiresTime'],
                    'expiresIn' => $response['data']['expiresIn'],
                    'refreshExpiresIn' => $response['data']['refreshExpiresIn'],
                    'updated_at' => date("Y-m-d H:i:s", time()),
                ]);
            }
        }
    }
});
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
Log::channel('crontab')->info('更新用refreshToken刷新accessToken完毕,RefreshToken.php 代码运行时间: ' . $executionTime . "秒");