<?php
/**
 * 初始化数据，每个类型只支持一个平台，请按注释填写相应信息，否则无法正常运行
 */
return [
    /**
     * 公众号配置信息
     */
    'account' => [
        'name' => '示例公众号',
        'type' => '1',
        'app_id' => 'account_app_id',
        'app_secret' => 'account_app_secret',
        'logo_url' => 'https://yuming.com/account.png',
        'account_type' => '4'  //1订阅号;2服务号;3认证订阅号;4认证服务号
    ],
    /**
     * 微信小程序配置信息
     */
    'wxapp' => [
        'name' => '示例微信小程序',
        'type' => '2',
        'app_id' => 'wxapp_app_id',
        'app_secret' => 'wxapp_app_secret',
        'logo_url' => 'https://yuming.com/wxapp.png',
    ],
    /**
     * 支付宝小程序配置信息
     */
    'aliapp' => [
        'name' => '示例支付宝小程序',
        'type' => '5',
        'app_id' => 'aliapp_app_id',
        'logo_url' => 'https://yuming.com/aliapp.png',
    ],
    /**
     * 百度小程序配置信息
     */
    'baiduapp' => [
        'name' => '示例百度小程序',
        'type' => '6',
        'app_id' => 'baiduapp_app_id',
        'app_key' => 'baiduapp_app_key',
        'app_secret' => 'baiduapp_app_secret',
        'logo_url' => 'https://yuming.com/baiduapp.png',
    ],
    /**
     * 抖音小程序配置信息
     */
    'toutiaoapp' => [
        'name' => '示例抖音小程序',
        'type' => '7',
        'app_id' => 'toutiaoapp_app_id',
        'app_secret' => 'toutiaoapp_app_secret',
        'logo_url' => 'https://yuming.com/toutiaoapp.png',
    ],
];
