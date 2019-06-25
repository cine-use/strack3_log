<?php
// 微信企业公众号配置
$config['message']['wechat'] = [
    'enable' => false,
    'corp_id' => 'ww700e6d7f6a05a632',
    'agent_id' => 1000012, // 如果有 agend_id 则填写
    'cover_pic' => STRACK_STATIC_DIR . '/images/wechat_cover.png',
    'secret' => 'WC6HAn40vzWB2BQ7UA-PC78QS6LhU_4j5Ts8QtzaAbg',
    // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
    'response_type' => 'array',
    'log' => [
        'level' => 'debug',
        'file' => LOG_DIR . '/wechat.log',
    ],
];
return $config;