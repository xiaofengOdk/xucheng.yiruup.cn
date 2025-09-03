<?php
//https://easywechat.com/6.x/ 企业微信 配置文件

return  [
    'app_id' => 'wx3cf0f39249eb0exx',
    'secret' => '1000006',
    'token' => 'MCKCnJnttbEox6B88gRxwp15P',
    'aes_key' => 'XFgy64em06wakhPYpcNmGg4Syn4XuXg74zqHxn2Wnkt', // 明文模式请勿填写 EncodingAESKey
    'cropid' => 'wwb1dd669aee5b9b8c', //企业ID
    /**
     * 接口请求相关配置，超时时间等，具体可用参数请参考：
     * https://github.com/symfony/symfony/blob/5.3/src/Symfony/Contracts/HttpClient/HttpClientInterface.php
     */
  'http' => [
    'throw'  => true, // 状态码非 200、300 时是否抛出异常，默认为开启
    'timeout' => 5.0,
    // 'base_uri' => 'https://qyapi.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri

    'retry' => true, // 使用默认重试配置
    //  'retry' => [
    //      // 仅以下状态码重试
    //      'status_codes' => [429, 500]
    //       // 最大重试次数
    //      'max_retries' => 3,
    //      // 请求间隔 (毫秒)
    //      'delay' => 1000,
    //      // 如果设置，每次重试的等待时间都会增加这个系数
    //      // (例如. 首次:1000ms; 第二次: 3 * 1000ms; etc.)
    //      'multiplier' => 3
      ],
];

