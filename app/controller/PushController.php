<?php

namespace app\controller;

use support\Db;
use support\Request;

//百度信息流主动推送数据   接口文档  https://dev2.baidu.com/content?sceneType=0&pageId=103515&nodeId=853&subhead=
class PushController
{
    public function index(Request $request)
    {
        //密钥 cb8e2549d55a5f5e3d9b92db7898df0e
        if ($request->method() == 'POST') {
            $secretKey = '9002ab456b2eff72a0d5254930fd244a';
            $signature1 = $this->paramsSign($request->post(), $secretKey);
            $data = $request->post();
            if (is_array($data) && isset($data[0]) && count($data[0]) > 0) {
                $status_200 = [ 'feedUserEffectStatus', 'feedUserRejectStatus', 'feedUserWaitChkStatus', 'feedUserNoRightStatus'];
                foreach ($data as $v) {
                    if (in_array($v['status'], $status_200)) {
                        $mentioned='';
                        $message = '';
                        if ($v['userId']) {
                            $message .= '百度账户ID为: ' . $v['userId'];
                            //子账户名称
                            $subUser = DB::table('baidu_xinxiliu_subuser')->where('userId', $v['userId'])->first();
                            if ($subUser != null) {
                                $message .= '  百度账户名为: ' . $subUser->userName;
                                //项目名称
                                $project = DB::table('baidu_xinxiliu_project')->where('subName', $subUser->userName)->first();
                                if ($project != null) {
                                    $message .= '  项目名称为: ' . $project->clientName;
                                }
                            }

                        }
                        if (isset($v['campaignId'])&&$v['campaignId']>0)
                            $message .= '  推广计划id为: ' . $v['campaignId'];

                        if (isset($v['creativeId'])&&$v['creativeId']>0)
                            $message .= '  推广创意id为: ' . $v['creativeId'];

                        if (isset($v['adgroupId'])&&$v['adgroupId']>0)
                            $message .= '  推广单元id为: ' . $v['adgroupId'];
                        //信息流创意审核,状态变化提醒
                        if ($v['changeType'] == 2) {
                            switch ($v['status']) {
                                case 'CREATIVE_AUDIT_OK':
                                    $message .= ' 创意审核通过';
                                    break;
                                case 'CREATIVE_AUDIT_REFUSE':
                                    $message .= ' 创意审核不通过';
                                    break;
                                case 'CREATIVE_AUDIT_INVALID':
                                    $message .= ' 无效';
                                    break;
                                default:
                            }

                        }
                        //信息流账户预算撞线
                        if ($v['changeType'] == 7) {
                            $mentioned.='@all';
                            switch ($v['status']) {
                                case 'USER_BUDGET_OFFLINE':
                                    $message .= ' 账户预算不足';
                                    break;
                                default:
                            }

                        }

                        //信息流计划预算撞线
                        if ($v['changeType'] == 8) {
                            $mentioned.='@all';
                            switch ($v['status']) {
                                case 'PLAN_BUDGET_OFFLINE':
                                    $message .= ' 计划预算不足';
                                    break;
                                default:
                            }

                        }

                        //信息流账户状态变化
                        if ($v['changeType'] == 201) {
                            $mentioned.='@all';
                            switch ($v['status']) {
                                case ' feedUserEffectStatus':
                                    $message .= '账户正常生效状态';
                                    break;
                                case ' feedUserRejectStatus':
                                    $message .= '信息流账户被拒绝状态';
                                    break;
                                case 'feedUserWaitChkStatus':
                                    $message .= '账户审核中状态';
                                    break;
                                case 'feedUserNoRightStatus':
                                    $message .= '账户被禁用状态';
                                    break;
                                default:
                            }

                        }
                        if ($v['changeType'] == 200) {
                            switch ($v['status']) {
                                case 'modAccountFeedBudget':
                                    $message .= ' 修改账户预算';
                                    break;
                                case 'shelveCampaignFeed':
                                    $message .= ' 暂停/启用计划';
                                    break;
                                case 'shelveCreativeFeed':
                                    $message .= ' 暂停/启用创意';
                                    break;
                                default:
                            }
                        }
                        if (isset($v['extraJson']))
                            $message .= '  原因:' . $v['extraJson'];

                        $message .= '  时间:' . date('Y-m-d H:i:s', $v['eventTime']);

                        $work_message = [
                            'msgtype' => 'text',
                            'text' => [
                                'content' => $message,
                                'mentioned_mobile_list' => [$mentioned]
                            ]
                        ];
                        var_dump($work_message);
                        $url = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=4742b357-8c97-4f7c-9702-86c7986cdf9c';
                       getData($url, json_encode($work_message));
                    }
                    return json(['code' => 0, 'message' => 'success', 'data' => $data, 'signature1' => $signature1]);
                    break;
                }
            }

            return json(['code' => 0, 'message' => 'success', 'data' => $data, 'signature1' => $signature1]);
        }
    }

    function paramsSign($params, $secretKey): string
    {
        $json = trim(json_encode($params));
        $str_padded = trim(base64_encode($json));
        if (strlen($str_padded) % 16) {
            $str_padded = str_pad($str_padded, strlen($str_padded) + 16 - strlen($str_padded) % 16, "\0");
        }
        return strtoupper(bin2hex(openssl_encrypt($str_padded, 'AES-128-CBC', substr($secretKey, 0, 16), OPENSSL_NO_PADDING, "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0")));
    }

}