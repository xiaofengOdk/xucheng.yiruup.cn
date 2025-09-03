<?php

namespace app\controller;
use support\Request;
class CaiwuController
{
    public function index(Request $request)
    {
        $uid=$request->get('uid', 55971812);
        $start_date = $request->get('start_date', '2024-06-01');
        $end_date = $request->get('end_date', '2024-06-30');
        $data=$this->get_data($uid, $start_date, $end_date);
        var_dump($data);
        return $data;
    }

    function get_data($uid, $start_date, $end_date)
    {
        $ch = curl_init();
        $url='https://caiwu.baidu.com/fp-mgr/payment/user_embed?orderby=paytime&&uid='.$uid.'&relate=0&fundtype=50&universaltype=0&begdate='.$start_date.'&enddate='.$end_date.'&ps=2000&searchby=son&username=&sortby=ASC&pn=1';
        echo $url.PHP_EOL;
        curl_setopt($ch, CURLOPT_URL, 'https://caiwu.baidu.com/fp-mgr/payment/user_embed?orderby=paytime&&uid='.$uid.'&relate=0&fundtype=50&universaltype=0&begdate='.$start_date.'&enddate='.$end_date.'&ps=2000&searchby=son&username=&sortby=ASC&pn=1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


        $headers = array();
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7';
        $headers[] = 'Accept-Language: zh-CN,zh;q=0.9';
        $headers[] = 'Cache-Control: no-cache';
        $headers[] = 'Connection: keep-alive';
        $headers[] = 'Cookie: uc_login_unique=5f14ad57f5a362775c9093bba688f4a2; BIDUPSID=7ACE1436A2FBE521D7C09BDCF6A681CA; PSTM=1716174214; BAIDUID=96E2357696E6A315CABDD0C60D73FCF4:FG=1; BAIDUID_BFESS=96E2357696E6A315CABDD0C60D73FCF4:FG=1; ZFY=cBmLEzD2cH9cAvbmaKezO3o32wY1nMokCcWAkZlK5RA:C; jsdk-uuid=68d31eaa-1da2-4d73-b847-ae3a15cb0b39; H_PS_PSSID=60338; uc_recom_mark=cmVjb21tYXJrXzgwMTE3NjA%3D; __cas__st__caiwu=58848d3db49922ba2abf60d2cd4ef15eb46463fc0940b6d7a21f07133b39be43382216da7119bd4dee06d083; __cas__id__caiwu=8011760; Hm_lvt_4d2607ab96885634369db4bef95c1e8c=1718346858,1719033633,1719848714; Hm_lvt_e79171af460bb49eba02c11939038ffd=1718346865,1719848725; Hm_lpvt_4d2607ab96885634369db4bef95c1e8c=1719883934; RT=\"z=1&dm=baidu.com&si=7f3a31d5-dfb5-4110-afd8-2e0ca657eb5d&ss=ly3qhkhs&sl=2&tt=j0&bcn=https%3A%2F%2Ffclog.baidu.com%2Flog%2Fweirwood%3Ftype%3Dperf&ld=271&cl=9kb&ul=9kg&hd=9v1\"; Hm_lpvt_e79171af460bb49eba02c11939038ffd=1719883947';
        $headers[] = 'Pragma: no-cache';
        $headers[] = 'Sec-Fetch-Dest: document';
        $headers[] = 'Sec-Fetch-Mode: navigate';
        $headers[] = 'Sec-Fetch-Site: none';
        $headers[] = 'Sec-Fetch-User: ?1';
        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
        $headers[] = 'Sec-Ch-Ua: \"Chromium\";v=\"124\", \"Google Chrome\";v=\"124\", \"Not-A.Brand\";v=\"99\"';
        $headers[] = 'Sec-Ch-Ua-Mobile: ?0';
        $headers[] = 'Sec-Ch-Ua-Platform: \"macOS\"';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return $result;

    }

}