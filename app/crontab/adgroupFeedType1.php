<?php
//信息流计划 单元  复制 删除
////查询单元https://api.baidu.com/json/feed/v1/AdgroupFeedService/getAdgroupFeed
if (!function_exists('getAdgroupFeed')) {
    function getAdgroupFeed($user_payload, $ids)
    {

        $adgroupFeedFields = [
            'adgroupFeedId' => '推广单元ID',
            'campaignFeedId' => '推广计划ID',
            'adgroupFeedName' => '推广单元名称',
            'pause' => '暂停/启用推广单元',
            'status' => '推广单元状态',
            'bid' => '出价',
            'ftypes' => '投放范围',
            'bidtype' => '优化目标和付费模式',
            'ocpc' => 'oCPC信息',
            'atpFeedId' => '定向包ID',
            'addtime' => '添加时间',
            'modtime' => '修改时间',
            'deliveryType' => '投放场景',
            'unefficientAdgroup' => '低效单元',
            'productSetId' => '商品组ID（仅商品推广）',
            'ftypeSelection' => '是否使用计划流量',
            'bidSource' => '是否使用计划出价',
            'unitOcpxStatus' => '单元学习状态',
            'atpName' => '定向包名称',
            'region' => '地域（省市区县）',
            'bizArea' => '预置商圈',
            'customArea' => '自定义商圈	',
            'place' => '场所',
            'useractiontype' => '用户到访类型',
            'age' => '年龄',
            'customAge' => '自定义年龄',
            'sex' => '性别',
            'lifeStage' => '人生阶段',
            'education' => '学历',
            'newInterests' => '新兴趣（兴趣2.0）',
            'keywords' => '意图词',
            'keywordsExtend' => '意图词用户行为',
            'crowd' => '人群包（定向人群）',
            'excludeCrowd' => '人群包（排除人群）',
            'excludeTrans' => '排除已转化人群',
            'excludeTransFilterTime' => '排除同主体已转化人群-时间过滤',
            'device' => '操作系统',
            'iosVersion' => 'iOS系统版本',
            'androidVersion' => 'Android系统版本',
            'androidBrands' => '手机品牌',
            'mobilePhonePrice' => '手机价格',
            'phonePrice' => '手机价格',
            'telecom' => '运营商',
            'app' => 'APP行为',
            'net' => '网络',
            'mediaPackage' => '百青藤精选媒体包',
            'mediaCategoriesBindType' => '百青藤媒体分类使用方式',
            'mediaCategories' => '百青藤媒体分类',
            'mediaidsBindType' => '百青藤自定义媒体包使用方式',
            'customMediaPackage' => '百青藤自定义媒体包',
            'deeplinkOnly' => '是否仅投放至允许调起的媒体',
            'articleType' => '文章分类',
            'eshopcrowds' => '已安装APP',
            'eldscrowds' => '电商推荐人群',
            'tradecrowds' => '电商推荐人群',
            'naUrl' => '调起URL',
            'url' => '推广URL',
            'premium' => '商品通投定向设置',
            'useKyp' => '商品智能买词定向定向设置',
            'rtaRedirect' => '商品RTA重定向设置',
            'paKeywords' => '商品单元意图词定向设置',
            'paCrowd' => '商品人群定向设置',
            'autoRegion' => '智能地域',
            'productIds' => '商品ID/产品ID',
            'userType' => '人群常住地类型',
        ];
        $adgroupFeedFields = array_keys($adgroupFeedFields);
        $user_payload['body'] = [
            'adgroupFeedFields' => $adgroupFeedFields,
            'ids' => $ids,
            'idType' => 1
        ];
        $jsonData = json_encode($user_payload);
        $url = 'https://api.baidu.com/json/feed/v1/AdgroupFeedService/getAdgroupFeed';
        $response = getData($url, $jsonData);
        $response = json_decode($response, true);
        return $response;

    }
}

//查询单元
$adgroupFeedData = getAdgroupFeed($user_payload, $campaignFeedIds);
//如果单元数据查询成功
if ($adgroupFeedData['header']['desc'] == 'success') {
    //批量新建单元的数组
    $data3 = [];
    //新建单元
    if (is_array($adgroupFeedData['body']['data']) && count($adgroupFeedData['body']['data']) > 0) {
        $data = [];
        //单元name 和 id 对应的数据
        $adgroupFeed_name_ids = [];
        foreach ($adgroupFeedData['body']['data'] as $k => $adgroupFeed) {
            //批量新建单元的数组
            $data2 = [];
            if(isset($feed_new_id_ids[$adgroupFeed['campaignFeedId']])) {
                //推广计划ID
                $data2['campaignFeedId'] = $feed_new_id_ids[$adgroupFeed['campaignFeedId']];
                //推广单元名称
                $data2['adgroupFeedName'] = explode('__', $adgroupFeed['adgroupFeedName'])[0]  . date("__md_H:i:s"). mt_rand(100, 10000);
                $data2['adgroupFeedName'] =truncateString($data2['adgroupFeedName']);
                $adgroupFeed_name_ids[$data2['adgroupFeedName']] = $adgroupFeed['adgroupFeedId'];
                //暂停/启用推广单元
                $data2['pause'] = $adgroupFeed['pause'];
                $data2['audience']['region'] = $adgroupFeed['audience']['region'];
                isset($adgroupFeed['audience']['bizArea']) && $data2['audience']['bizArea'] = $adgroupFeed['audience']['bizArea'];

                isset($adgroupFeed['audience']['customArea']) && $data2['audience']['customArea'] = $adgroupFeed['audience']['customArea'];

                isset($adgroupFeed['audience']['place']) && $data2['audience']['place'] = $adgroupFeed['audience']['place'];

                isset($adgroupFeed['audience']['useractiontype']) && $data2['audience']['useractiontype'] = $adgroupFeed['audience']['useractiontype'];

                isset($adgroupFeed['audience']['age']) && $data2['audience']['age'] = $adgroupFeed['audience']['age'];

                isset($adgroupFeed['audience']['customAge']) && $data2['audience']['customAge'] = $adgroupFeed['audience']['customAge'];

                isset($adgroupFeed['audience']['sex']) && $data2['audience']['sex'] = $adgroupFeed['audience']['sex'];

                isset($adgroupFeed['audience']['lifeStage']) && $data2['audience']['lifeStage'] = $adgroupFeed['audience']['lifeStage'];
                isset($adgroupFeed['audience']['education']) && $data2['audience']['education'] = $adgroupFeed['audience']['education'];
                isset($adgroupFeed['audience']['newInterests']) && $data2['audience']['newInterests'] = $adgroupFeed['audience']['newInterests'];
                isset($adgroupFeed['audience']['keywords']) && $data2['audience']['keywords'] = $adgroupFeed['audience']['keywords'];
                isset($adgroupFeed['audience']['keywordsExtend']) && $data2['audience']['keywordsExtend'] = $adgroupFeed['audience']['keywordsExtend'];
                isset($adgroupFeed['audience']['crowd']) && $data2['audience']['crowd'] = $adgroupFeed['audience']['crowd'];
                isset($adgroupFeed['audience']['excludeCrowd']) && $data2['audience']['excludeCrowd'] = $adgroupFeed['audience']['excludeCrowd'];
                isset($adgroupFeed['audience']['excludeTrans']) && $data2['audience']['excludeTrans'] = $adgroupFeed['audience']['excludeTrans'];

                isset($adgroupFeed['audience']['excludeTransFilterTime']) && $data2['audience']['excludeTransFilterTime'] = $adgroupFeed['audience']['excludeTransFilterTime'];

                isset($adgroupFeed['audience']['device']) && $data2['audience']['device'] = $adgroupFeed['audience']['device'];

                isset($adgroupFeed['audience']['iosVersion']) && $data2['audience']['iosVersion'] = $adgroupFeed['audience']['iosVersion'];

                isset($adgroupFeed['audience']['androidVersion']) && $data2['audience']['androidVersion'] = $adgroupFeed['audience']['androidVersion'];

                isset($adgroupFeed['audience']['androidBrands']) && $data2['audience']['androidBrands'] = $adgroupFeed['audience']['androidBrands'];

                isset($adgroupFeed['audience']['mobilePhonePrice']) && $data2['audience']['mobilePhonePrice'] = $adgroupFeed['audience']['mobilePhonePrice'];

                isset($adgroupFeed['audience']['phonePrice']) && $data2['audience']['phonePrice'] = $adgroupFeed['audience']['phonePrice'];

                isset($adgroupFeed['audience']['telecom']) && $data2['audience']['telecom'] = $adgroupFeed['audience']['telecom'];

                isset($adgroupFeed['audience']['app']) && $data2['audience']['app'] = $adgroupFeed['audience']['app'];

                isset($adgroupFeed['audience']['net']) && $data2['audience']['net'] = $adgroupFeed['audience']['net'];

                isset($adgroupFeed['audience']['mediaPackage']) && $data2['audience']['mediaPackage'] = $adgroupFeed['audience']['mediaPackage'];

                isset($adgroupFeed['audience']['mediaCategoriesBindType']) && $data2['audience']['mediaCategoriesBindType'] = $adgroupFeed['audience']['mediaCategoriesBindType'];

                isset($adgroupFeed['audience']['mediaCategories']) && $data2['audience']['mediaCategories'] = $adgroupFeed['audience']['mediaCategories'];

                isset($adgroupFeed['audience']['mediaidsBindType']) && $data2['audience']['mediaidsBindType'] = $adgroupFeed['audience']['mediaidsBindType'];

                isset($adgroupFeed['audience']['customMediaPackage']) && $data2['audience']['customMediaPackage'] = $adgroupFeed['audience']['customMediaPackage'];

                isset($adgroupFeed['audience']['deeplinkOnly']) && $data2['audience']['deeplinkOnly'] = $adgroupFeed['audience']['deeplinkOnly'];

                isset($adgroupFeed['audience']['articleType']) && $data2['audience']['articleType'] = $adgroupFeed['audience']['articleType'];

                isset($adgroupFeed['audience']['eshopcrowds']) && $data2['audience']['eshopcrowds'] = $adgroupFeed['audience']['eshopcrowds'];

                isset($adgroupFeed['audience']['eldscrowds']) && $data2['audience']['eldscrowds'] = $adgroupFeed['audience']['eldscrowds'];

                isset($adgroupFeed['audience']['tradecrowds']) && $data2['audience']['tradecrowds'] = $adgroupFeed['audience']['tradecrowds'];

                isset($adgroupFeed['audience']['naUrl']) && $data2['audience']['naUrl'] = $adgroupFeed['audience']['naUrl'];

                isset($adgroupFeed['audience']['url']) && $data2['audience']['url'] = $adgroupFeed['audience']['url'];

                isset($adgroupFeed['audience']['premium']) && $data2['audience']['premium'] = $adgroupFeed['audience']['premium'];

                isset($adgroupFeed['audience']['useKyp']) && $data2['audience']['useKyp'] = $adgroupFeed['audience']['useKyp'];

                //isset($adgroupFeed['audience']['rtaRedirect'])&&$data2['audience']['rtaRedirect'] = $adgroupFeed['audience']['rtaRedirect'];

                //isset($adgroupFeed['audience']['paKeywords'])&&$data2['audience']['paKeywords'] = $adgroupFeed['audience']['paKeywords'];

                // isset($adgroupFeed['audience']['paCrowd'])&&$data2['audience']['paCrowd'] = $adgroupFeed['audience']['paCrowd'];

                // isset($adgroupFeed['audience']['autoRegion'])&&$data2['audience']['autoRegion'] = $adgroupFeed['audience']['autoRegion'];

                //isset($adgroupFeed['audience']['productIds'])&&$data2['audience']['productIds'] = $adgroupFeed['audience']['productIds'];

                isset($adgroupFeed['audience']['userType']) && $data2['audience']['userType'] = $adgroupFeed['audience']['userType'];
                $data2['ftypeSelection'] = $adgroupFeed['ftypeSelection'];


                $data2['ftypes'] = $adgroupFeed['ftypes'];
                $data2['bidtype'] = $adgroupFeed['bidtype'];
                $data2['ocpc'] = $adgroupFeed['ocpc'];
                isset($adgroupFeed['productSetId']) && $data2['productSetId'] = $adgroupFeed['productSetId'];

                isset($adgroupFeed['atpFeedId']) && $data2['atpFeedId'] = $adgroupFeed['atpFeedId'];

                isset($adgroupFeed['deliveryType']) && $data2['deliveryType'] = $adgroupFeed['deliveryType'];

                isset($adgroupFeed['ftypeSelection']) && $data2['ftypeSelection'] = $adgroupFeed['ftypeSelection'];
                //1 - 单元单独设置出价
                //2 - 使用计划出价
                //使用计划出价时，不需要传bid、bidtype以及oCPC中的出价相关字段，优先使用计划出价设置
                //出价上移名单使用字段，名单外使用无效。
                if (isset($adgroupFeed['bidSource']) && $adgroupFeed['bidSource'] == 2) {
                    $data2['bidSource'] = $adgroupFeed['bidSource'];
                    unset($data2['bid']);
                    unset($data2['bidtype']);
                    unset($data2['ocpc']['ocpcBid']);
                    if (isset($data2['ocpc']['deepOcpcBid']))
                        unset($data2['ocpc']['deepOcpcBid']);
                }

                isset($adgroupFeed['urlType']) && $data2['urlType'] = $adgroupFeed['urlType'];

                isset($adgroupFeed['miniProgram']) && $data2['miniProgram'] = $adgroupFeed['miniProgram'];

                isset($adgroupFeed['broadCastInfo']) && $data2['broadCastInfo'] = $adgroupFeed['broadCastInfo'];

                isset($adgroupFeed['url']) && $data2['url'] = $adgroupFeed['url'];
                $data[$k] = $data2;
            }

        }
        $user_payload['body'] = [
            'adgroupFeedTypes' => $data,
        ];
        $jsonData = json_encode($user_payload);
        $url = 'https://api.baidu.com/json/feed/v1/AdgroupFeedService/addAdgroupFeed';
        $adgroupFeedResponseData = getData($url, $jsonData);
        $adgroupFeedResponseData = json_decode($adgroupFeedResponseData, true);
        //批量新建单元成功之后，批量新建计划
        if (is_array($adgroupFeedResponseData['body']['data']) && count($adgroupFeedResponseData['body']['data']) > 0) {
            //要复制的单元 id 跟新建出来的单元 id 对应关系
            $adgroupFeed_new_name_ids = [];
            foreach ($adgroupFeedResponseData['body']['data'] as $k => $adgroupFeedType) {
                if (isset($adgroupFeed_name_ids[$adgroupFeedType['adgroupFeedName']])) {
                    $adgroupFeed_new_name_ids[$adgroupFeed_name_ids[$adgroupFeedType['adgroupFeedName']]] = $adgroupFeedType['adgroupFeedId'];
                }
            }
            // var_dump($adgroupFeed_name_ids);
            //var_dump($adgroupFeed_new_name_ids);
            //新建单元
            require app_path() . '/crontab/creativeFeedType1.php';
        } else {
            echo '账户: ' . $userName . '新建计划失败，请排查原因' . json_encode($adgroupFeedResponseData['header']);
        }
    }
}

