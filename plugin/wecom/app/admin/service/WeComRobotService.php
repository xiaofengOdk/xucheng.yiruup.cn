<?php
/**
 * @desc WeComRobotService.php 企业微信通知机器人
 * @author lotus
 * @date 2024/5/6 11:32
 */

declare(strict_types=1);

namespace plugin\wecom\app\admin\service;

class WeComRobotService
{
    /**
     * 企业微信通知机器人
     * @param array $args
     * @return bool|string
     */
    public static function weComRobot(array $args)
    {
        $title =  "## ".$args['title']."\n";
        $message = "> ". $args['message'];

        $data = [
            'msgtype' => 'markdown',
            'markdown' => [
                'content' => $title.$message,
            ]
        ];
        return  self::request_by_curl($args['url'], json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @desc: 自定义请求类
     * @param string $remote_server
     * @param string $postString
     * @return bool|string
     */
    private static function request_by_curl(string $remote_server, string $postString)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
