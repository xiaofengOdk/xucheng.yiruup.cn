<?php
//项目日预算 企微提醒
namespace app\crontab;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as Request1;
use GuzzleHttp\Psr7\Response;
use QL\QueryList;
use support\Db;
use support\Log;
use support\Redis;
use app\model\CampaginBaidu;
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../support/bootstrap.php';


// 记录开始时间
$startTime = microtime(true);
$responseCampaginBaidu = new  CampaginBaidu();
$startsTime =  date('Y-m-d H:i:s', strtotime('+1 days'));
$endsTime =   date('Y-m-d H:i:s', strtotime('+3 months'));

$admins = [];
$admins1 = Db::connection('mysql')->table('wa_admins')
    ->select('id', 'userName', 'weixin', 'sweixin', 'weixin_push')
    ->where('status', null)
    ->get()->toArray();
foreach ($admins1 as $admin) {
    $admins[$admin->id] = $admin;
}
Db::connection('mysql')->table('baidu_xinxiliu_project_list')->select('id', 'projectName','hid', 'loginurl','login_name','login_pwd','project_type', 'conversions_day')
    ->where('conversions_day','>', 0)
    ->where('project_type', 1)
    ->where('id',69)
    ->chunkById(30, function ($project_list) use ($admins,$responseCampaginBaidu,$startsTime,$endsTime) {
        foreach ($project_list as $project_list_item) {
            $project = Db::connection('mysql')->table('baidu_xinxiliu_project')
                ->leftJoin('baidu_xinxiliu_subuser', 'baidu_xinxiliu_project.subName', '=', 'baidu_xinxiliu_subuser.userName')
                ->leftJoin('baidu_xinxiliu_refreshToken', 'baidu_xinxiliu_subuser.masterUid', '=', 'baidu_xinxiliu_refreshToken.userId')  // 关联refreshToken表
                ->select(
                    'baidu_xinxiliu_project.id',
                    'baidu_xinxiliu_project.id',
                    'baidu_xinxiliu_project.clientName',
                    'baidu_xinxiliu_project.sellId',
                    'baidu_xinxiliu_project.youhuashiId',
                    'baidu_xinxiliu_subuser.userId',
                    'baidu_xinxiliu_subuser.userName',
                    'baidu_xinxiliu_subuser.id as sid',
                    'baidu_xinxiliu_refreshToken.accessToken' ,
                    'baidu_xinxiliu_refreshToken.expiresTime'  
                )
                ->where('baidu_xinxiliu_project.clientName', $project_list_item->projectName)
                ->where('baidu_xinxiliu_project.status', 1)
                ->orderBy('baidu_xinxiliu_project.id', 'DESC')
                ->get()->toArray();
            $subuser_map = array_column($project, 'userName');
            $userIds = array_column($project, 'userId');
            //userName和 userId 映射关系
            $a1 = [];
            foreach ($project as $v) {
                $a1[$v->userName] = $v->userId;
            }
            $result = [];
            $date = date('Y-m-d');
            $start_timestamp = ($date . ' 00:00:00');
            // 获取第二天 00:00 的时间戳
            $end_timestamp = strtotime($date . ' +1 day');
            $end_timestamp = date('Y-m-d 00:00:00', $end_timestamp);
            if ($project_list_item->hid == 1) {//悟空
                $url = explode('/', $project_list_item->loginurl);
                // 取出前6段组成需要的路径：
                // [0] => https:
                // [1] => (空字符串)
                // [2] => ljff.wukongphp.com
                // [3] => index.php
                // [4] => wukljf
                $url = $url[0] . '//' . $url[2] . '/' . $url[3] . '/' . $url[4];
                $url = str_replace('https://', 'http://', $url);
                $credentials = [
                    'username' => $project_list_item->login_name,
                    'password' => $project_list_item->login_pwd,
                ];
                //易和皮肤
                /*
                $url = 'https://ljff.wukongphp.com/index.php/wukljf';
                $credentials = [
                    'username' => 'yihepf',
                    'password' => 'yihepf888666888666888',
                ];
                */
                //登录地址
                $loginUrl = $url . '/login/index.html';

                try {
                    $jar = new CookieJar();
                    $headers = [
                        'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                        'accept-language' => 'zh-CN,zh;q=0.9',
                        'cache-control' => 'no-cache',
                        'pragma' => 'no-cache',
                        'priority' => 'u=0, i',
                        'sec-ch-ua' => '"Google Chrome";v="137", "Chromium";v="137", "Not/A)Brand";v="24"',
                        'sec-ch-ua-mobile' => '?0',
                        'sec-ch-ua-platform' => '"macOS"',
                        'sec-fetch-dest' => 'document',
                        'sec-fetch-mode' => 'navigate',
                        'sec-fetch-site' => 'none',
                        'sec-fetch-user' => '?1',
                        'upgrade-insecure-requests' => '1',
                        'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',
                    ];
                    $timeout = 30;
                    // 使用 QueryList HTTP 发起 POST 登录请求，并保存 Cookie
                    $ql = QueryList::post($loginUrl, $credentials, [
                        'cookies' => $jar,
                        //设置超时时间，单位：秒
                        'timeout' => $timeout,
                        'headers' => $headers,
                        'verify' => false,
                    ]);
                    // 判断是否登录成功 查找包含 /main.html 的链接
                    $login_success = $ql->find('iframe#Mainindex')->attr('src');
                    if (strpos($login_success, 'main') !== false) {
                        /*
                        // 设置 Cookie 文件存储路径
                        $cookieFile = runtime_path() . '/cookies/' . $credentials['username'] . '.cookie';
                        // 确保目录存在
                        if (!is_dir(dirname($cookieFile))) {
                            mkdir(dirname($cookieFile), 0755, true);
                        };
                        $cookieArr = $jar->toArray();
                        file_put_contents($cookieFile, json_encode($cookieArr));

                        //悟空首页
                        $indexurl = $url . '/index/index.html';
                        $response = $ql->get($indexurl, $credentials, [
                            'cookies' => $jar,
                            //设置超时时间，单位：秒
                            'timeout' => $timeout,
                            'headers' => $headers,
                        ]);
                        //悟空产品页
                        $producturl = $url . '/product/index.html';
                        $response = $ql->get($producturl, $credentials, [
                            'cookies' => $jar,
                            //设置超时时间，单位：秒
                            'timeout' => $timeout,
                            'headers' => $headers,
                        ]);
                        */
                        //产品复制分析
                        $productformurl = $url . '/form/index.html';

                        $response = $ql->post($productformurl,
                            [
                                'pro_id' => '',
                                'starttime' => $start_timestamp,
                                'endtime' => $end_timestamp,
                            ],
                            [
                                'cookies' => $jar,
                                //设置超时时间，单位：秒
                                'timeout' => $timeout,
                                'headers' => $headers,
                                'verify' => false,
                            ]);
                        $data = $response->find('#selprowchat option')->map(function ($item) {
                            return [
                                'product_id' => $item->attr('value'),
                                'product_name' => $item->text()
                            ];
                        });
                        $postList = [];
                        $postheaders = [
                            'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                            'accept-language' => 'zh-CN,zh;q=0.9',
                            'cache-control' => 'no-cache',
                            'content-type' => 'application/x-www-form-urlencoded',
                            'origin' => $productformurl,
                            'pragma' => 'no-cache',
                            'priority' => 'u=0, i',
                            'referer' => $productformurl,
                            'sec-ch-ua' => '"Google Chrome";v="137", "Chromium";v="137", "Not/A)Brand";v="24"',
                            'sec-ch-ua-mobile' => '?0',
                            'sec-ch-ua-platform' => '"macOS"',
                            'sec-fetch-dest' => 'iframe',
                            'sec-fetch-mode' => 'navigate',
                            'sec-fetch-site' => 'same-origin',
                            'sec-fetch-user' => '?1',
                            'upgrade-insecure-requests' => '1',
                            'Connection' => 'keep-alive',
                            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',
                        ];
                        foreach ($data as $v) {
                            if ($v['product_id'] == '') continue;
                            $product_name = $v['product_name'];
                            $parts = explode('-', $product_name);
                            // 取倒数第2个和最后一个元素
                            $lastTwoParts = array_slice($parts, -2);
                            // 拼接成字符串
                            $subName = implode('-', $lastTwoParts);
                            //var_dump($subName);
                            if (in_array($subName, $subuser_map)) {
                                $postList[] =
                                    new Request1('POST', $productformurl . '?sele_pro=' . $v['product_id'], $postheaders, http_build_query([
                                        'pro_id' => $v['product_id'],
                                        'wukurl' => '',
                                        'starttime' => $start_timestamp,
                                        'endtime' => $end_timestamp,
                                    ]));
                            }
                        }
                        // 发起并发 POST
                        $ql->multiPost($postList)
                            ->concurrency(8)// 并发数
                            ->withOptions([
                                'timeout' => $timeout,
                                'verify_peer' => false,//跳过 SSL 验证
                                'verify_host' => false,
                                'verify' => false,
                                'cookies' => $jar,
                            ])
                            ->success(function (QueryList $ql, Response $res, $index) use (&$result, $date, $a1) {
                                //var_dump($res->getStatusCode());
                                $product_id = $ql->find('select[name=pro_id] option[selected]')->value;
                                $product_name = $ql->find('select[name=pro_id] option[selected]')->text();
                                $parts = explode('-', $product_name);
                                // 取倒数第2个和最后一个元素
                                $lastTwoParts = array_slice($parts, -2);
                                // 拼接成字符串
                                $subName = implode('-', $lastTwoParts);
                                $total =
                                    $ql->find('table.order_table:eq(0) tr:last td:eq(2)') // 获取最后一个 tr 的第3个 td（索引从0开始）
                                    ->text(); // 获取文本内容
                                // echo 'id为' . $product_id . '____户名为:' . $subName . '____总加粉量为:' . $total . PHP_EOL;
                                if (isset($a1[$subName])) {
                                    //当天数据要是没有就创建表数据
                                    $is_exist = Db::connection('mysql')->table('baidu_xinxiliu_moment_reportdata')
                                        ->where(['subNameId' => intval($a1[$subName]), 'currentDay' => $date])
                                        ->exists();
                                    if (!$is_exist) {
                                        Db::connection('mysql')->table('baidu_xinxiliu_moment_reportdata')->insert(
                                            [
                                                'subNameId' => intval($a1[$subName]), 'currentDay' => $date, 'trueWeixinNum' => intval($total)
                                            ]);
                                    } else {
                                        Db::connection('mysql')->table('baidu_xinxiliu_moment_reportdata')
                                            ->where(['subNameId' => intval($a1[$subName]), 'currentDay' => $date])
                                            ->update(['trueWeixinNum' => intval($total)]);
                                    }
                                }
                                $result[$product_id] = [
                                    'userId' => isset($a1[$subName]) ? $a1[$subName] : 0,
                                    'total' => $total
                                ];
                                // echo "Response from {$endpoints[$index]}: $result\n";
                            })
                            ->error(function (QueryList $ql, $reason, $index) {
                                echo "Error in request to {$index}: $reason\n";
                            })
                            ->send();
                        $totalSum = array_sum(array_column($result, 'total'));
                        //假设totalSum是14
                        $totalSum = 14;
                        
                        if ($totalSum + 1 >= $project_list_item->conversions_day) {
                         // 优化：先过滤出有效的项目
                            $validProjects = array_filter($project, function($item) {
                                return !empty($item->accessToken) && strtotime($item->expiresTime) - time() > 7200;
                            });
                            if (!empty($validProjects)) {
                                foreach($validProjects as $project_item){
                                     $updatetime = Redis::get('baidu_xinxiliu_project_updatetime_converday_' . $date . $project_item->id);
                                    if(!$updatetime){   
                                       // 将对象转换为数组，给CampaginBaidu模型使用
                                       $projectArray = (array) $project_item; 
                                                                 
                                         $campaignFeed = $responseCampaginBaidu->getCampaignFeedCrontab([], $projectArray, $project_item->accessToken, null, $startsTime, $endsTime);
                                         if($campaignFeed['code']==0){
                                           Redis::set('baidu_xinxiliu_project_updatetime_converday_' . $date . $project_item->id, 1);
                                           var_dump($campaignFeed['message']);

                                        } else {
                                           // 根据错误类型决定是否重试
                                           $errorCode = $campaignFeed['code'] ?? 'unknown';
                                           var_dump($campaignFeed['message']);
                                       }
                                    }
                                 }
                            }
                        }
                        // if ($totalSum + 1 >= $project_list_item->conversions_day) {
                        //     //是否提醒过
                        //     $is_push = Redis::get('weixin_push_converday_' . $date . $project_list_item->id);
                        //     if (!$is_push) {
                        //         $weixin_message = "项目：<font color=\"info\">" . $project_list_item->projectName . "</font> 现加粉总数为<font color=\"info\">" . $totalSum . "</font>。
                        //                      >快接近于日加粉量<font color=\"comment\">" . $project_list_item->conversions_day . "</font>。
                        //                      >请<font color=\"info\">相关同事注意</font>。
                        //                      >时间: " . date("Y-m-d H:i:s") . "。\n";
                        //         if (isset($admins[$project[0]->sellId]))
                        //             $weixin_message .= ">销售: <font color=\"info\">" . $admins[$project[0]->sellId]->userName . "， </font>。\n";
                        //         if (isset($admins[$project[0]->youhuashiId])) {
                        //             $weixin_message .= ">优化师: <font color=\"info\">" . $admins[$project[0]->youhuashiId]->userName . "</font>。\n";
                        //             $weixin_message .= "<@" . $admins[$project[0]->youhuashiId]->weixin . ">";
                        //         }
                        //         if (isset($admins[$project[0]->youhuashiId]->weixin_push) && $admins[$project[0]->youhuashiId]->weixin_push != null)
                        //             push_work_weixin($admins[$project[0]->youhuashiId]->weixin_push, $weixin_message);
                        //         $workweixin = push_work_weixin('4742b357-8c97-4f7c-9702-86c7986cdf9c', $weixin_message);
                        //         //只提醒一次
                        //         if (is_array($workweixin) && $workweixin['errcode'] == 0)
                        //             Redis::set('weixin_push_converday_' . $date . $project_list_item->id, 1);
                        //     }
                        // }
                    } else {
                        Log::channel('conversionsday')->info($project_list_item->projectName . ' 账号密码错误 ' . $project_list_item->loginurl . ' 账号：' . $project_list_item->login_name . ' 密码: ' . $project_list_item->login_pwd);
                    }
                } catch (RequestException $e) {
                    print_r($e->getRequest());
                    echo 'Http Error';
                }
            }
            elseif ($project_list_item->hid == 2) { //好多粉
                $url = 'http://i.hduofen.cn/';
                $credentials = [
                    'user_name' => $project_list_item->login_name,
                    'user_pwd' => $project_list_item->login_pwd,
                ];
                //登录地址
                $loginUrl = 'https://api.hduofen.cn/sem/login/login';
                try {
                    $jar = new CookieJar();
                    $headers = [
                        'Accept: application/json, text/javascript, */*; q=0.01',
                        'Accept-Language: zh-CN,zh;q=0.9',
                        'Cache-Control: no-cache',
                        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                        'Origin: https://i.hduofen.cn',
                        'Pragma: no-cache',
                        'Priority: u=1, i',
                        'Referer: https://i.hduofen.cn/',
                        'Sec-Ch-Ua: "Google Chrome";v="137", "Chromium";v="137", "Not/A)Brand";v="24"',
                        'Sec-Ch-Ua-Mobile: ?0',
                        'Sec-Ch-Ua-Platform: "macOS"',
                        'Sec-Fetch-Dest: empty',
                        'Sec-Fetch-Mode: cors',
                        'Sec-Fetch-Site: same-site',
                        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'
                    ];
                    $timeout = 30;
                    // 使用 QueryList HTTP 发起 POST 登录请求，并保存 Cookie
                    $ql = QueryList::post($loginUrl, $credentials, [
                        'cookies' => $jar,
                        //设置超时时间，单位：秒
                        'timeout' => $timeout,
                        'headers' => $headers,
                        'verify' => false,
                    ]);
                    //登录成功
                    if (strpos($ql->getHtml(), '登录成功') !== false) {
                        $login = json_decode($ql->getHtml(), true);
                        //   var_dump($login);
                        //实时到粉转化详情
                        $queryHkzsConversionListUrl = 'https://api.hduofen.cn/sem/qwhkzs/queryHkzsConversionList';
                        $headers = [
                            'accept' => 'application/json, text/javascript, */*; q=0.01',
                            'accept-language' => 'zh-CN,zh;q=0.9',
                            'access-token' => $login['data']['token'],
                            'cache-control' => 'no-cache',
                            'content-type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                            'origin' => 'https://i.hduofen.cn',
                            'pragma' => 'no-cache',
                            'priority' => 'u=1, i',
                            'referer' => 'https://i.hduofen.cn/',
                            'sec-ch-ua' => '"Google Chrome";v="137", "Chromium";v="137", "Not/A)Brand";v="24"',
                            'sec-ch-ua-mobile' => '?0',
                            'sec-ch-ua-platform' => '"macOS"',
                            'sec-fetch-dest' => 'empty',
                            'sec-fetch-mode' => 'cors',
                            'sec-fetch-site' => 'same-site',
                            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'
                        ];
                        $credentials = [
                            'page' => 1,
                            'count' => 1000,
                            'searchText' => '',
                            'orderby' => '',
                            'start_time' => $start_timestamp,
                            'end_time' => $end_timestamp,
                            'user_level' => $login['data']['vip_level'],
                            'seo' => '',
                            'sele_url' => '',
                            'sele_cvt_type' => ''
                        ];
                        $conversionList = $ql->post($queryHkzsConversionListUrl, $credentials, [
                            'cookies' => $jar,
                            //设置超时时间，单位：秒
                            'timeout' => $timeout,
                            'headers' => $headers,
                            'verify' => false,
                        ]);
                        $conversionListData = json_decode($conversionList->getHtml(), true);
                        $a2 = [];
                        foreach ($a1 as $k => $v) {
                            $a2[$k] = 0;
                        }
                        if ($conversionListData['code'] == 0) {
                            //  var_dump($conversionListData);
                            foreach ($conversionListData['data']['list'] as $conversion) {
                                //  var_dump($conversion);
                                $url_remark = trim($conversion['url_remark']);
                                if (isset($a2[$url_remark])) {
                                    $a2[$url_remark]++;
                                }
                            }
                            foreach ($a1 as $k => $v) {
                                $result[$v] = [
                                    'userId' => $v,
                                    'total' => isset($a2[$k]) ? $a2[$k] : 0,
                                ];
                                //当天数据要是没有就创建表数据
                                $is_exist = Db::connection('mysql')->table('baidu_xinxiliu_moment_reportdata')
                                    ->where(['subNameId' => intval($v), 'currentDay' => $date])
                                    ->exists();
                                if (!$is_exist) {
                                    Db::connection('mysql')->table('baidu_xinxiliu_moment_reportdata')->insert(
                                        [
                                            'subNameId' => intval($v), 'currentDay' => $date, 'trueWeixinNum' => intval($a2[$k])
                                        ]);
                                } else {
                                    Db::connection('mysql')->table('baidu_xinxiliu_moment_reportdata')
                                        ->where(['subNameId' => intval($v), 'currentDay' => $date])
                                        ->update(['trueWeixinNum' => intval($a2[$k])]);
                                }
                            }
                            $totalSum = array_sum(array_column($result, 'total'));
                            if ($totalSum + 1 >= $project_list_item->conversions_day) {
                            
                            }
                            // if ($totalSum + 1 >= $project_list_item->conversions_day) {
                            //     //是否提醒过
                            //     $is_push = Redis::get('weixin_push_converday_' . $date . $project_list_item->id);
                            //     if (!$is_push) {
                            //         $weixin_message = "项目：<font color=\"info\">" . $project_list_item->projectName . "</font> 现加粉总数为<font color=\"info\">" . $totalSum . "</font>。
                            //                  >快接近于日加粉量<font color=\"comment\">" . $project_list_item->conversions_day . "</font>。
                            //                  >请<font color=\"info\">相关同事注意</font>。
                            //                  >时间: " . date("Y-m-d H:i:s") . "。\n";
                            //         if (isset($admins[$project[0]->sellId]))
                            //             $weixin_message .= ">销售: <font color=\"info\">" . $admins[$project[0]->sellId]->userName . " </font>。\n";
                            //         if (isset($admins[$project[0]->youhuashiId])) {
                            //             $weixin_message .= ">优化师: <font color=\"info\">" . $admins[$project[0]->youhuashiId]->userName . "</font>。\n";
                            //             $weixin_message .= "<@" . $admins[$project[0]->youhuashiId]->weixin . ">";
                            //         }
                            //         if (isset($admins[$project[0]->youhuashiId]->weixin_push) && $admins[$project[0]->youhuashiId]->weixin_push != null)
                            //             push_work_weixin($admins[$project[0]->youhuashiId]->weixin_push, $weixin_message);
                            //         $workweixin = push_work_weixin('4742b357-8c97-4f7c-9702-86c7986cdf9c', $weixin_message);
                            //         //只提醒一次
                            //         if (is_array($workweixin) && $workweixin['errcode'] == 0)
                            //             Redis::set('weixin_push_converday_' . $date . $project_list_item->id, 1);
                            //     }
                            // }
                        }
                    } else {
                        return json(['code' => 601, 'result' => [], 'msg' => '好多粉账号密码错误']);
                    }
                } catch (RequestException $e) {
                    print_r($e->getRequest());
                    echo 'Http Error';
                }

            }

        }

    });
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
Log::channel('conversionsday')->info('日加粉量 到量提醒数据完毕,ConversionsDay.php 代码运行时间: ' . $executionTime . "秒");