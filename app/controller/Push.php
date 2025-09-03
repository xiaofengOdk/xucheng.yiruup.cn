<?php

namespace app\controller;

use support\Db;
use support\Redis;
use support\Request;

//http://xucheng.qingqingxq.com/ad/?bd_vid=__BD_VID__&aid=__IDEA_ID__&pid=__PLAN_ID__&uid=__UNIT_ID__&userid=__USER_ID__& click_id=__CLICK_ID__&idfa=__IDFA__&imei_md5=__IMEI__&androidid=__ANDROIDID1__&androidid_md5=__ANDROIDID__&ip=__IP__&ua=__UA__&os=__OS__&ts=__TS__& ext_info=__EXT_INFO__&mac_md5=__MAC1__&mac=__MAC__&oaid=__OAID__&oaid_md5=__OAID_MD5__&comb_id=__COMBID__&size=__SIZE__&deeplink_url=__DEEPLINK_URL__


class PushController
{
    public function index(Request $request)
    {
        $baidu_data = $request->get();
        //echo 'ad------'.PHP_EOL;
        //var_dump($request->header());
        //var_dump($baidu_data);
        Db::table('ad_tracking_info')->insert($baidu_data);
    }
}