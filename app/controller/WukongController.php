<?php

namespace app\controller;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as Request1;
use GuzzleHttp\Psr7\Response;
use QL\QueryList;
use support\Db;
use support\Log;
use support\Request;

class WukongController
{
    protected $userName;

    public function __construct()
    {
        $this->userName = Request()->get('userName', 'QD-大步流星');
    }

    public function index(Request $request)
    {
        $subNameIds = array_keys($request->post());
        if (is_array($subNameIds) && count($subNameIds) > 0) {
            $subuser = Db::connection('mysql2')->table('baidu_xinxiliu_subuser')
                ->select('userId', 'userName')
                ->whereIn('userId', $subNameIds)
                ->get()->toArray();
            $subuser_map = array_column($subuser, 'userName');
            //userName和 userId 映射关系
            $a1 = [];
            foreach ($subuser as $v) {
                $a1[$v->userName] = $v->userId;
            }
            $date = $request->get('date', date('Y-m-d'));
            $start_timestamp = strtotime($date);
            $end_timestamp = strtotime($date . ' +1 day'); // 获取第二天 00:00 的时间戳
            $product = Db::connection('wukong')->table('wuk_product')
                ->select('gdtid', 'id')
                ->whereIn('gdtid', $subuser_map)
                ->get()->toArray();
            $product_id_map = array_column($product, 'id');
            //userId productid 映射关系
            $a2 = [];
            foreach ($product as $v) {
                $a2[$v->id] = $a1[$v->gdtid];
            }
            var_dump($a2);
            //直接删除=>6 已加好友=>3  发起回话->4  回话后删除=>5
            if ($product) {
                //SELECT product_id, COUNT(*) AS total FROM wuk_order WHERE firstadd_time >= 1750089600 AND wchatstate IN (3, 4, 5, 6) AND product_id IN (574, 490, 364) GROUP BY product_id;
                $order_count = Db::connection('wukong')->table('wuk_order')
                    ->select('product_id', DB::raw('COUNT(*) as total'))
                    ->where('firstadd_time', '>=', $start_timestamp)
                    ->where('firstadd_time', '<=', $end_timestamp)
                    ->whereIn('product_id', $product_id_map)
                    ->whereIn('wchatstate', [3, 4, 5, 6])
                    ->groupBy('product_id')
                    ->get()
                    //->pluck('total', 'product_id')
                    ->toArray();
                $result = [];
                foreach ($order_count as $v) {
                    $result[$v->product_id] = [
                        'total' => $v->total,
                        'userId' => isset($a2[$v->product_id]) ? $a2[$v->product_id] : 0,
                    ];
                }
                foreach ($product_id_map as $v) {
                    if (!isset($result[$v])) {
                        $result[$v] = [
                            'total' => 0,
                            'userId' => isset($a2[$v]) ? $a2[$v] : 0,
                        ];
                    }
                }
                foreach ($result as $v) {
                    //当天数据要是没有就创建表数据
                    $is_exist = Db::connection('mysql2')->table('baidu_xinxiliu_moment_reportdata')
                        ->where(['subNameId' => intval($v['userId']), 'currentDay' => $date])
                        ->exists();
                    if (!$is_exist) {
                        Db::connection('mysql2')->table('baidu_xinxiliu_moment_reportdata')->insert(
                            [
                                'subNameId' => intval($v['userId']), 'currentDay' => $date, 'trueWeixinNum' => intval($v['total'])
                            ]);
                    } else {
                        Db::connection('mysql2')->table('baidu_xinxiliu_moment_reportdata')
                            ->where(['subNameId' => intval($v['userId']), 'currentDay' => $date])
                            ->update(['trueWeixinNum' => intval($v['total'])]);
                    }
                }
                return json(['result' => $result]);
            }
        }

    }

    public function index2(Request $request)
    {
        $projectid = intval(mcrypt_decode($request->get('project', '4226TI-4csKBv_qPfwJDaOUItH9hx3FfuENLqoINnkVIUFDf94aNWf_0VcpiApvipH12Q3FNtWKyGkY')));
        if ($projectid > 0) {
            $projectFirst = Db::connection('mysql2')
                ->table('baidu_xinxiliu_project')
                ->select('id', 'clientName', 'subName')
                ->where('id', $projectid)->first();
            if ($projectFirst) {
                //悟空 好多粉 登录信息
                $projectList = Db::connection('mysql2')
                    ->table('baidu_xinxiliu_project_list')
                    ->select('id', 'projectName', 'hid', 'loginurl', 'login_name', 'login_pwd', 'project_type')
                    ->where('projectName', trim($projectFirst->clientName))->first();
                if ($projectList && $projectList->project_type == 1) {
                        $date = $request->get('date', date('Y-m-d'));
                        $date = date("Y-m-d", strtotime($date));
                        $start_timestamp = date('Y-m-d 00:00:00', strtotime($date));
                        // 获取第二天 00:00 的时间戳
                        $end_timestamp = strtotime($date . ' +1 day');
                        $end_timestamp = date('Y-m-d 00:00:00', $end_timestamp);
                        $project = Db::connection('mysql2')
                            ->table('baidu_xinxiliu_project')
                            ->select('id', 'clientName', 'subName')
                            ->where('clientName', $projectFirst->clientName)
                            ->get()
                            ->toArray();
                        $subuser_map = array_column($project, 'subName');
                        if (is_array($subuser_map) && count($subuser_map) > 0) {
                            $subuser = Db::connection('mysql2')->table('baidu_xinxiliu_subuser')
                                ->select('userId', 'userName')
                                ->whereIn('userName', $subuser_map)
                                ->get()->toArray();
                            //userName和 userId 映射关系
                            $a1 = $a2 = [];
                            foreach ($subuser as $v) {
                                $a2[$v->userName] = $v->userId;
                            }
                            //只查有消费的户 减少网络请求
                            $cost = Db::connection('mysql2')->table('baidu_xinxiliu_reportdata')
                                ->select('userId', 'cost', 'eventDate')
                                ->where('eventDate', $date)
                                ->whereIn('userId', $a2)
                                ->get()->toArray();
                            $cost_user_map = array_column($cost, 'userId');
                            foreach ($a2 as $k => $v) {
                                if (in_array($v, $cost_user_map))
                                    $a1[$k] = $v;
                            }
                            $subuser_map = array_keys($a1);

                            $result = [];

                            if ($projectList->hid == 1) {//悟空
                                $url = explode('/', $projectList->loginurl);
                                // 取出前6段组成需要的路径：
                                // [0] => https:
                                // [1] => (空字符串)
                                // [2] => ljff.wukongphp.com
                                // [3] => index.php
                                // [4] => wukljf
                                $url = $url[0] . '//' . $url[2] . '/' . $url[3] . '/' . $url[4];
                                $url = str_replace('https://', 'http://', $url);
                                $credentials = [
                                    'username' => $projectList->login_name,
                                    'password' => $projectList->login_pwd,
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
                                            //var_dump($product_name);
                                            $parts = explode('-', $product_name);
                                            // 取倒数第2个和最后一个元素
                                            $lastTwoParts = array_slice($parts, -2);
                                            // 拼接成字符串
                                            $subName = implode('-', $lastTwoParts);

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
                                            ->success(function (QueryList $ql, Response $res, $index) use (&$result, $date, $a1, $projectFirst) {
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
                                        var_dump($result);
                                        $totalSum = 0;
                                        if (is_array($result) && count($result) > 0) {
                                            $totalSum = array_sum(array_column($result, 'total'));
                                            //当天数据要是没有就创建表数据
                                            $is_exist = Db::connection('mysql2')->table('baidu_xinxiliu_project_trueweixinfollow')
                                                ->where(['projectName' => $projectFirst->clientName, 'eventDate' => $date])
                                                ->exists();
                                            if (!$is_exist) {
                                                Db::connection('mysql2')->table('baidu_xinxiliu_project_trueweixinfollow')->insert(['projectName' => $projectFirst->clientName, 'eventDate' => $date, 'trueWeixinFollowSuccessConversions' => $totalSum]);
                                            } else {
                                                if ($totalSum > 0)
                                                    Db::connection('mysql2')->table('baidu_xinxiliu_project_trueweixinfollow')
                                                        ->where(['projectName' => $projectFirst->clientName, 'eventDate' => $date])
                                                        ->update(['trueWeixinFollowSuccessConversions' => $totalSum]);
                                            }
                                        }
                                        unset($ql);
                                        return json(['code' => 200, 'result' => ['date' => $date, 'totalSum' => $totalSum], 'msg' => '从悟空同步数据成功']);
                                    } else {
                                        Log::channel('autologin')->info($projectList->projectName . ' 账号密码错误 ' . $projectList->loginurl . ' 账号：' . $projectList->login_name . ' 密码: ' . $projectList->login_pwd);
                                        return json(['code' => 601, 'result' => [], 'msg' => '悟空账号密码错误']);
                                    }
                                } catch (RequestException $e) {
                                    print_r($e->getRequest());
                                    echo 'Http Error';
                                }
                            } elseif ($projectList->hid == 2) { //好多粉
                                $url = 'http://i.hduofen.cn/';
                                $credentials = [
                                    'user_name' => $projectList->login_name,
                                    'user_pwd' => $projectList->login_pwd,
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

                                            }
                                            $totalSum = 0;
                                            if (is_array($result) && count($result) > 0) {
                                                $totalSum = array_sum(array_column($result, 'total'));
                                                //当天数据要是没有就创建表数据
                                                $is_exist = Db::connection('mysql2')->table('baidu_xinxiliu_project_trueweixinfollow')
                                                    ->where(['projectName' => $projectFirst->clientName, 'eventDate' => $date])
                                                    ->exists();
                                                if (!$is_exist) {
                                                    Db::connection('mysql2')->table('baidu_xinxiliu_project_trueweixinfollow')->insert(['projectName' => $projectFirst->clientName, 'eventDate' => $date, 'trueWeixinFollowSuccessConversions' => $totalSum]);
                                                } else {
                                                    if ($totalSum > 0)
                                                        Db::connection('mysql2')->table('baidu_xinxiliu_project_trueweixinfollow')
                                                            ->where(['projectName' => $projectFirst->clientName, 'eventDate' => $date])
                                                            ->update(['trueWeixinFollowSuccessConversions' => $totalSum]);
                                                }
                                            }
                                            unset($ql);
                                            return json(['code' => 200, 'result' => ['date' => $date, 'totalSum' => $totalSum], 'msg' => '从好多粉同步数据成功']);
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

                    }
            }
        }
    }

    public function index1(Request $request)
    {
        $projectid = intval(mcrypt_decode($request->get('project', '4226TI-4csKBv_qPfwJDaOUItH9hx3FfuENLqoINnkVIUFDf94aNWf_0VcpiApvipH12Q3FNtWKyGkY')));
        if ($projectid > 0) {
            $projectFirst = Db::connection('mysql2')
                ->table('baidu_xinxiliu_project')
                ->select('id', 'clientName', 'subName')
                ->where('id', $projectid)->first();
            if ($projectFirst) {
                //悟空 好多粉 登录信息
                $projectList = Db::connection('mysql2')
                    ->table('baidu_xinxiliu_project_list')
                    ->select('id', 'projectName', 'hid', 'loginurl', 'login_name', 'login_pwd','project_type')
                    ->where('projectName', trim($projectFirst->clientName))->first();
                if ($projectList && $projectList->project_type == 1) {
                    //传过来的 userId
                    $subNameIds = array_keys($request->post());
                    if (is_array($subNameIds) && count($subNameIds) > 0) {
                        $subuser = Db::connection('mysql2')->table('baidu_xinxiliu_subuser')
                            ->select('userId', 'userName')
                            ->whereIn('userId', $subNameIds)
                            ->get()->toArray();
                        $subuser_map = array_column($subuser, 'userName');
                        //userName和 userId 映射关系
                        $a1 = [];
                        foreach ($subuser as $v) {
                            $a1[$v->userName] = $v->userId;
                        }
                    }
                    $result = [];
                    $date = $request->get('date', date('Y-m-d'));
                    $start_timestamp = ($date . ' 00:00:00');
                    // 获取第二天 00:00 的时间戳
                    $end_timestamp = strtotime($date . ' +1 day');
                    $end_timestamp = date('Y-m-d 00:00:00', $end_timestamp);

                    if ($projectList->hid == 1) {//悟空
                        $url = explode('/', $projectList->loginurl);
                        // 取出前6段组成需要的路径：
                        // [0] => https:
                        // [1] => (空字符串)
                        // [2] => ljff.wukongphp.com
                        // [3] => index.php
                        // [4] => wukljf
                        $url = $url[0] . '//' . $url[2] . '/' . $url[3] . '/' . $url[4];
                       // $url = str_replace('https://', 'http://', $url);
                        $credentials = [
                            'username' => $projectList->login_name,
                            'password' => $projectList->login_pwd,
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
                                            $is_exist = Db::connection('mysql2')->table('baidu_xinxiliu_moment_reportdata')
                                                ->where(['subNameId' => intval($a1[$subName]), 'currentDay' => $date])
                                                ->exists();
                                            if (!$is_exist) {
                                                Db::connection('mysql2')->table('baidu_xinxiliu_moment_reportdata')->insert(
                                                    [
                                                        'subNameId' => intval($a1[$subName]), 'currentDay' => $date, 'trueWeixinNum' => intval($total)
                                                    ]);
                                            } else {
                                                Db::connection('mysql2')->table('baidu_xinxiliu_moment_reportdata')
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
                                // var_dump($result);
                                return json(['code' => 200, 'result' => $result, 'msg' => '从悟空同步数据成功']);
                            } else {
                                Log::channel('autologin')->info($projectList->projectName . ' 账号密码错误 ' . $projectList->loginurl . ' 账号：' . $projectList->login_name . ' 密码: ' . $projectList->login_pwd);
                                return json(['code' => 601, 'result' => [], 'msg' => '悟空账号密码错误']);
                            }
                        } catch (RequestException $e) {
                            print_r($e->getRequest());
                            echo 'Http Error';
                        }
                    }
                    elseif ($projectList->hid == 2) { //好多粉
                        $url = 'http://i.hduofen.cn/';
                        $credentials = [
                            'user_name' => $projectList->login_name,
                            'user_pwd' => $projectList->login_pwd,
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
                                        $is_exist = Db::connection('mysql2')->table('baidu_xinxiliu_moment_reportdata')
                                            ->where(['subNameId' => intval($v), 'currentDay' => $date])
                                            ->exists();
                                        if (!$is_exist) {
                                            Db::connection('mysql2')->table('baidu_xinxiliu_moment_reportdata')->insert(
                                                [
                                                    'subNameId' => intval($v), 'currentDay' => $date, 'trueWeixinNum' => intval($a2[$k])
                                                ]);
                                        } else {
                                            Db::connection('mysql2')->table('baidu_xinxiliu_moment_reportdata')
                                                ->where(['subNameId' => intval($v), 'currentDay' => $date])
                                                ->update(['trueWeixinNum' => intval($a2[$k])]);
                                        }
                                    }

                                    return json(['code' => 200, 'result' => $result, 'msg' => '从好多粉同步数据成功']);
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
            }
        }
    }

}