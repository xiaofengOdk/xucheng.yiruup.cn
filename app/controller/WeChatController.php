<?php
namespace app\controller;

use support\Request;
use EasyWeChat\OfficialAccount\Application;

//doc https://easywechat.com/6.x/
class WeChatController
{
    public function index(Request $request)
    {
       $url= 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=4742b357-8c97-4f7c-9702-86c7986cdf9c';
        $data = [
            'msgtype'=>'text',
            'text'=>[
                'content'=>'信息流测试！',
                'mentioned_mobile_list'=>['@all']
            ]
        ];
        $response = getData($url, json_encode($data));
    }
}