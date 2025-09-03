<?php

namespace app\controller;

use support\Db;
use support\Request;

//http://xucheng.qingqingxq.com/ad/?bd_vid=__BD_VID__&aid=__IDEA_ID__&pid=__PLAN_ID__&uid=__UNIT_ID__&userid=__USER_ID__& click_id=__CLICK_ID__&idfa=__IDFA__&imei_md5=__IMEI__&androidid=__ANDROIDID1__&androidid_md5=__ANDROIDID__&ip=__IP__&ua=__UA__&os=__OS__&ts=__TS__& ext_info=__EXT_INFO__&mac_md5=__MAC1__&mac=__MAC__&oaid=__OAID__&oaid_md5=__OAID_MD5__&comb_id=__COMBID__&size=__SIZE__&deeplink_url=__DEEPLINK_URL__


class AdController
{
    public function index(Request $request)
    {
        $baidu_data = $request->get();
        //echo 'ad------'.PHP_EOL;
        //var_dump($request->header());
        //var_dump($baidu_data);
        Db::table('ad_tracking_info')->insert($baidu_data);

        return json(['code' => 0, 'msg' => 1]);
    }

    public function bd(Request $request)
    {
        $baidu_data = $request->get();
        if (isset($baidu_data['url']) && $baidu_data['url']) {
            $url = explode("?", $baidu_data['url'])[0];
            $bd_vid = explode("=", $baidu_data['url'])[1];
            $parts = parse_url($baidu_data['url']);
            parse_str($parts['query'], $query);

            if ($url && $bd_vid) {
                $subuser = $baidu_data['u'] ?? '';
                Db::table('ad_brush')->insert([
                    'url' => $url,
                    'bd_vid' => $query['bd_vid'],
                    'useragent' => $baidu_data['useragent'],
                    'httpreferer' => $request->header('referer'),
                    'subuser' => $subuser,
                    'ip' => $request->getRealIp(),
                    'requested' => $request->header('x-requested-with'),
                    't' => time(),
                ]);
            }
        }
        $jsCode = 'console.log(' . $bd_vid . ')';
        return jsonp(['code' => 0, 'msg' => $jsCode]);
    }

    public function bd1(Request $request)
    {
        $baidu_data = $request->get();
        var_dump($baidu_data);
        var_dump($request->header());
        var_dump($request->header('referer'));
        if (isset($baidu_data['url']) && $baidu_data['url']) {
            $url = explode("?", $baidu_data['url'])[0];
            $bd_vid = explode("=", $baidu_data['url'])[1];
            if ($url && $bd_vid) {
                $subuser = $baidu_data['u'] ?? '';
            }
        }
        $jsCode = 'console.log(' . $bd_vid . ');';
        return jsonp(['code' => 0, 'msg' => $jsCode]);
    }

    public function bd2(Request $request)
    {
        $data = array_merge($request->post(), $request->header());
        $data['connection_type'] = isset($request->post('connection')['type'])?$request->post('connection')['type']:'';
        $data['connection_downlink'] = $request->post('connection')['downlink'];
       // echo 111;
      //  var_dump($data);
        $client_logs = [
            'currentUrl' => isset($data['currentUrl']) ? $data['currentUrl'] : '',
            'app_version' => isset($data['appVersion']) ? $data['appVersion'] : '',
            'platform' => isset($data['platform']) ? $data['platform'] : '',
            'language' => isset($data['language']) ? $data['language'] : '',
            'cookie_enabled' => isset($data['cookieEnabled']) && $data['cookieEnabled'] === 'true' ? 1 : 0,
            'hardware_concurrency' => isset($data['hardwareConcurrency']) ? $data['hardwareConcurrency'] : '',
            'max_touch_points' => isset($data['maxTouchPoints']) ? $data['maxTouchPoints'] : '',
            'screen' => isset($data['screen']) ? $data['screen'] : '',
            'dpr' => isset($data['dpr']) ? $data['dpr'] : '',
            'timezone' => isset($data['timezone']) ? $data['timezone'] : '',
            'referrer' => isset($data['referrer']) ? $data['referrer'] : '',
            'online' => isset($data['online']) && $data['online'] === 'true' ? 1 : 0,

            'x_real_ip' => isset($data['x-real-ip']) ? $data['x-real-ip'] : '',
            'host' => isset($data['host']) ? $data['host'] : '',
            'x_forwarded_proto' => isset($data['x-forwarded-proto']) ? $data['x-forwarded-proto'] : '',
            'content_length' => isset($data['content-length']) ? $data['content-length'] : '',
            'accept' => isset($data['accept']) ? $data['accept'] : '',
            'user_agent' => isset($data['user-agent']) ? $data['user-agent'] : '',
            'content_type' => isset($data['content-type']) ? $data['content-type'] : '',
            'origin' => isset($data['origin']) ? $data['origin'] : '',
            'x_requested_with' => isset($data['x-requested-with']) ? $data['x-requested-with'] : '',
            'sec_fetch_site' => isset($data['sec-fetch-site']) ? $data['sec-fetch-site'] : '',
            'sec_fetch_mode' => isset($data['sec-fetch-mode']) ? $data['sec-fetch-mode'] : '',
            'sec_fetch_dest' => isset($data['sec-fetch-dest']) ? $data['sec-fetch-dest'] : '',
            'x_from_h3_trnet' => isset($data['x-from-h3-trnet']) && $data['x-from-h3-trnet'] === 'true' ? 1 : 0,
            'x_bd_traceid' => isset($data['x-bd-traceid']) ? $data['x-bd-traceid'] : '',
            'referer_header' => isset($data['referer']) ? $data['referer'] : '',
            'accept_encoding' => isset($data['accept-encoding']) ? $data['accept-encoding'] : '',
            'accept_language' => isset($data['accept-language']) ? $data['accept-language'] : '',
            'connection_type' => isset($data['connection_type']) ? $data['connection_type'] : '',
            'connection_downlink' => isset($data['connection_downlink']) ? $data['connection_downlink'] : '',

            'created_at' => date('Y-m-d H:i:s'),
        ];

        Db::table('client_logs')->insert($client_logs);
        return json(['code' => 0, 'msg' => $request->header()]);
    }

}
