<?php
//信息流 创意  复制 删除

if (!function_exists('getCreativeFeed')) {

    function getCreativeFeed($user_payload, $ids)
    {
        $creativeFeedFields = [
            'creativeFeedId' => '创意ID',
            'adgroupFeedId' => '推广单元ID',
            'materialstyle' => '样式',
            'creativeFeedName' => '创意名称',
            'pause' => '暂停/启用创意',
            'material' => '物料内容',
            'status' => '创意状态',
            'refusereason' => '拒绝原因',
            'expmask' => '产品标示位',
            'changeorder' => '三图换顺序开关',
            'commentnum' => '显示评论数开关',
            'readnum' => '显示阅读数开关',
            'playnum' => '显示播放数开关',
            'ideaType' => '创意类型',
            'showMt' => '程序化创意展示样式',
            'addtime' => '创意添加时间',
            'progFlag' => '程序化创意工具标识',
            'approvemsgnew' => '格式化的拒绝理由',
            'auditTimeModel' => '产生类型',
            'template' => '商品目录创意参数',
            'huitus' => '商品目录创意绑定慧图模板ID',
        ];
        $creativeFeedFields = array_keys($creativeFeedFields);
        $user_payload['body'] = [
            'creativeFeedFields' => $creativeFeedFields,
            'ids' => $ids,
            'idType' => 1
        ];
        $jsonData = json_encode($user_payload);
        $url = 'https://api.baidu.com/json/feed/v1/CreativeFeedService/getCreativeFeed';
        $response = getData($url, $jsonData);
        $response = json_decode($response, true);
        return $response;
    }
}
//查询创意
$adgroupFeedData = getCreativeFeed($user_payload, $campaignFeedIds);
echo '列出创意信息' . PHP_EOL;
var_dump($adgroupFeedData);
echo '列出复制出来的创意信息' . PHP_EOL;
//如果单元数据查询成功
if ($adgroupFeedData['header']['desc'] == 'success') {
    $data = [];
    foreach ($adgroupFeedData['body']['data'] as $k => $adgroupFeed) {
        if(isset($adgroupFeed_new_name_ids[$adgroupFeed['adgroupFeedId']])) {
            $adgroupFeed['material'] = json_decode($adgroupFeed['material'], true);
            $data[$k] = [
                //$feed_new_id_ids[$adgroupFeed['campaignFeedId']]
                //单元 id
                'adgroupFeedId' => $adgroupFeed_new_name_ids[$adgroupFeed['adgroupFeedId']],
                'materialstyle' => $adgroupFeed['materialstyle'],
                'creativeFeedName' => explode('__', $adgroupFeed['creativeFeedName'])[0]  . date("__md_H:i:s"). mt_rand(100, 10000),
                'pause' => false,

                'ideaType' => $adgroupFeed['ideaType'],
                'commentnum' => $adgroupFeed['commentnum'],
                'readnum' => $adgroupFeed['readnum'],
                'playnum' => $adgroupFeed['playnum'],
                'progFlag' => $adgroupFeed['progFlag'],
            ];

            $data[$k]['creativeFeedName'] =truncateString($data[$k]['creativeFeedName']);
            isset($adgroupFeed['material']['verticalVideos']) && $data[$k]['material']['verticalVideos'] = $adgroupFeed['material']['verticalVideos'];
            isset($adgroupFeed['material']['title']) && $data[$k]['material']['title'] = $adgroupFeed['material']['title'];
            isset($adgroupFeed['material']['brand']) && $data[$k]['material']['brand'] = $adgroupFeed['material']['brand'];
            isset($adgroupFeed['material']['subtitle']) && $data[$k]['material']['subtitle'] = $adgroupFeed['material']['subtitle'];
            isset($adgroupFeed['material']['userPortrait']) && $data[$k]['material']['userPortrait'] = $adgroupFeed['material']['userPortrait'];
            isset($adgroupFeed['material']['pictures']) && $data[$k]['material']['pictures'] = $adgroupFeed['material']['pictures'];
            isset($adgroupFeed['material']['pictureWidth']) && $data[$k]['material']['pictureWidth'] = $adgroupFeed['material']['pictureWidth'];
            isset($adgroupFeed['material']['pictureHeight']) && $data[$k]['material']['pictureHeight'] = $adgroupFeed['material']['pictureHeight'];
            isset($adgroupFeed['material']['url']) && $data[$k]['material']['url'] = $adgroupFeed['material']['url'];
            isset($adgroupFeed['material']['newDownloadUrl']) && $data[$k]['material']['newDownloadUrl'] = $adgroupFeed['material']['newDownloadUrl'];
            isset($adgroupFeed['material']['videoid']) && $data[$k]['material']['videoid'] = $adgroupFeed['material']['videoid'];
            isset($adgroupFeed['material']['posterid']) && $data[$k]['material']['posterid'] = $adgroupFeed['material']['posterid'];
            isset($adgroupFeed['material']['poster']) && $data[$k]['material']['poster'] = $adgroupFeed['material']['poster'];
            isset($adgroupFeed['material']['horizontalCoverId']) && $data[$k]['material']['horizontalCoverId'] = $adgroupFeed['material']['horizontalCoverId'];
            isset($adgroupFeed['material']['horizontalCover']) && $data[$k]['material']['horizontalCover'] = $adgroupFeed['material']['horizontalCover'];
            isset($adgroupFeed['material']['videoText1']) && $data[$k]['material']['videoText1'] = $adgroupFeed['material']['videoText1'];
            isset($adgroupFeed['material']['videoText2']) && $data[$k]['material']['videoText2'] = $adgroupFeed['material']['videoText2'];
            isset($adgroupFeed['material']['naUrl']) && $data[$k]['material']['naUrl'] = $adgroupFeed['material']['naUrl'];
            isset($adgroupFeed['material']['monitorUrl']) && $data[$k]['material']['monitorUrl'] = $adgroupFeed['material']['monitorUrl'];
            isset($adgroupFeed['material']['exposureUrl']) && $data[$k]['material']['exposureUrl'] = $adgroupFeed['material']['exposureUrl'];
            isset($adgroupFeed['material']['monitorUrlType']) && $data[$k]['material']['monitorUrlType'] = $adgroupFeed['material']['monitorUrlType'];
            isset($adgroupFeed['material']['exposureUrlType']) && $data[$k]['material']['exposureUrlType'] = $adgroupFeed['material']['exposureUrlType'];
            isset($adgroupFeed['material']['ulkUrl']) && $data[$k]['material']['ulkUrl'] = $adgroupFeed['material']['ulkUrl'];
            isset($adgroupFeed['material']['ulkScheme']) && $data[$k]['material']['ulkScheme'] = $adgroupFeed['material']['ulkScheme'];
            isset($adgroupFeed['material']['ideaPluginGroup']) && $data[$k]['material']['ideaPluginGroup'] = $adgroupFeed['material']['ideaPluginGroup'];
            isset($adgroupFeed['material']['pluginIds']) && $data[$k]['material']['pluginIds'] = $adgroupFeed['material']['pluginIds'];
            isset($adgroupFeed['material']['elements']['titles']) && $data[$k]['material']['elements']['titles'] = $adgroupFeed['material']['elements']['titles'];
            isset($adgroupFeed['material']['elements']['pictureSingle']) && $data[$k]['material']['elements']['pictureSingle'] = $adgroupFeed['material']['elements']['pictureSingle'];
            isset($adgroupFeed['material']['elements']['pictureLarge']) && $data[$k]['material']['elements']['pictureLarge'] = $adgroupFeed['material']['elements']['pictureLarge'];
            isset($adgroupFeed['material']['elements']['picture3Fixed']) && $data[$k]['material']['elements']['picture3Fixed'] = $adgroupFeed['material']['elements']['picture3Fixed'];
            isset($adgroupFeed['material']['elements']['videoHorizontal']['videoId']) && $data[$k]['material']['elements']['videoHorizontal']['videoId'] = $adgroupFeed['material']['elements']['videoHorizontal']['videoId'];
            isset($adgroupFeed['material']['elements']['videoHorizontal']['videoUrl']) && $data[$k]['material']['elements']['videoHorizontal']['videoUrl'] = $adgroupFeed['material']['elements']['videoHorizontal']['videoUrl'];
            if (isset($adgroupFeed['material']['elements']['videoHorizontal']) && count($adgroupFeed['material']['elements']['videoHorizontal']) > 0) {
                foreach ($adgroupFeed['material']['elements']['videoHorizontal'] as $_k => $videoHorizontal) {
                    $data[$k]['material']['elements']['videoHorizontal'][$_k]['videoUrl'] = $videoHorizontal['videoUrl'];
                    $data[$k]['material']['elements']['videoHorizontal'][$_k]['videoId'] = $videoHorizontal['videoId'];
                    $data[$k]['material']['elements']['videoHorizontal'][$_k]['poster'] = $videoHorizontal['poster'];
                    isset($videoHorizontal['horizontalCover']) && $data[$k]['material']['elements']['videoHorizontal'][$_k]['horizontalCover'] = $videoHorizontal['horizontalCover'];
                    isset($videoHorizontal['verticalCover']) && $data[$k]['material']['elements']['videoHorizontal'][$_k]['verticalCover'] = $videoHorizontal['verticalCover'];
                }
            }

            if (isset($adgroupFeed['material']['elements']['videoVertical']) && count($adgroupFeed['material']['elements']['videoVertical']) > 0) {
                foreach ($adgroupFeed['material']['elements']['videoVertical'] as $_k => $videoVertical) {
                    $data[$k]['material']['elements']['videoVertical'][$_k]['videoUrl'] = $videoVertical['videoUrl'];
                    $data[$k]['material']['elements']['videoVertical'][$_k]['videoId'] = $videoVertical['videoId'];
                    $data[$k]['material']['elements']['videoVertical'][$_k]['poster'] = $videoVertical['poster'];
                    isset($videoVertical['horizontalCover']) && $data[$k]['material']['elements']['videoVertical'][$_k]['horizontalCover'] = $videoVertical['horizontalCover'];
                    isset($videoVertical['verticalCover']) && $data[$k]['material']['elements']['videoVertical'][$_k]['verticalCover'] = $videoVertical['verticalCover'];


                }
            }
            $data[$k]['material'] = json_encode($data[$k]['material']);
        }

    }
    var_dump($data);
    //创建创意
    $user_payload['body'] = [
        'creativeFeedTypes' => $data,
    ];

    $jsonData = json_encode($user_payload);
    $url = 'https://api.baidu.com/json/feed/v1/CreativeFeedService/addCreativeFeed';
    $creativeFeedResponseData = getData($url, $jsonData);
    $creativeFeedResponseData = json_decode($creativeFeedResponseData, true);
    var_dump($creativeFeedResponseData);
    /*
    //批量新建创意成功之后，批量删除计划
    if (is_array($creativeFeedResponseData['body']['data']) && count($creativeFeedResponseData['body']['data']) > 0) {
        $url='https://api.baidu.com/json/feed/v1/CampaignFeedService/deleteCampaignFeed';
        $user_payload['body'] = [
            'campaignFeedIds' => $campaignFeedIds,
        ];
        $jsonData = json_encode($user_payload);
        $response = getData($url, $jsonData);
        $response = json_decode($response, true);
    }else{
        echo '账户: '.$userName.'新建单元失败，请排查原因'.json_encode($creativeFeedResponseData['body']['header']);
    }
    */
}
