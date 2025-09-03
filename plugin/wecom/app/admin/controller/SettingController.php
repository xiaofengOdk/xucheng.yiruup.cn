<?php

namespace plugin\wecom\app\admin\controller;

use plugin\admin\app\model\Option;
use plugin\wecom\api\Wecom;
use plugin\wecom\app\admin\service\WeComRobotService;
use support\Request;
use support\Response;

class SettingController
{
    /**
     * 设置页
     * @return Response
     */
    public function index()
    {
        return view('setting/index');
    }

    /**
     * 获取设置
     * @return Response
     */
    public function get(): Response
    {
        $name = Wecom::SETTING_OPTION_NAME;
        $setting = Option::where('name', $name)->value('value');
        $setting = $setting ? json_decode($setting, true) : [
            'url' => '请输入微信机器人url',
        ];
        return json(['code' => 0, 'msg' => 'ok', 'data' => $setting]);
    }

    /**
     * 更改设置
     * @param Request $request
     * @return Response
     */
    public function save(Request $request): Response
    {
        $data = [
            'url' => $request->post('url'),
        ];
        $value = json_encode($data);
        $name = Wecom::SETTING_OPTION_NAME;
        $option = Option::where('name', $name)->first();
        if ($option) {
            Option::where('name', $name)->update(['value' => $value]);
        } else {
            $option = new Option();
            $option->name = $name;
            $option->value = $value;
            $option->save();
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 消息测试
     * @param Request $request
     * @return Response
     */
    public function test(Request $request): Response
    {
        $option = Option::where('name', Wecom::SETTING_OPTION_NAME)->value('value');
        if (!$option) {
            return json(['code' => 403, 'msg' => '请先配置企业微信通知地址']);
        }

        $jsonArr = json_decode($option,true);
        $args = [
            'url' => $jsonArr['url'],
            'title' => $request->post('title', '测试通知'),
            'message' => $request->post('Content', '这是一个测试内容'),
        ];
        WeComRobotService::weComRobot($args);
        return json(['code' => 0, 'msg' => 'ok', 'data' => $option]);
    }

}