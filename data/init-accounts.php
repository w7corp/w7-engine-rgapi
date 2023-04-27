<?php
/**
 * 本地开发需设定的初始化数据，每个类型只支持一个平台，请按注释填写相应信息，否则无法正常运行
 * 一个支持公众号和微信小程序的完整示例：
 * return [
 *      'account' => [
 *          'name' => '示例公众号',
 *          'type' => '1',
 *          'app_id' => 'wxc375ef84a72b281f',
 *          'app_secret' => '1a1b124bd33940c3f50bafbf9646541d',
 *          'logo_url' => 'https://yuming.com/account.png',
 *          'account_type' => '4'
 *      ],
 *      'wxapp' => [
 *          'name' => '示例微信小程序',
 *          'type' => '2',
 *          'app_id' => 'wxc375ef84a72b281f',
 *          'app_secret' => '1a1b124bd33940c3f50bafbf9646541d',
 *          'logo_url' => 'https://yuming.com/wxapp.png',
 *      ],
 *      'aliapp' => [],
 *      'baiduapp' => [],
 *      'toutiaoapp' => [],
 * ];
 */
return [
    /**
     * 公众号配置信息，按注释信息配置。不支持公众号则需设为空数组
     * [
     *      'name' => '示例公众号',
     *      'type' => '1',
     *      'app_id' => 'wxc375ef84a72b281f',
     *      'app_secret' => '1a1b124bd33940c3f50bafbf9646541d',
     *      'logo_url' => 'https://yuming.com/account.png',
     *      'account_type' => '4'  //1订阅号;2服务号;3认证订阅号;4认证服务号
     * ]
     */
    'account' => [],
    /**
     * 微信小程序配置信息，按注释信息配置。不支持微信小程序则需设为空数组
     * [
     *      'name' => '示例微信小程序',
     *      'type' => '2',
     *      'app_id' => 'wxc375ef84a72b281f',
     *      'app_secret' => '1a1b124bd33940c3f50bafbf9646541d',
     *      'logo_url' => 'https://yuming.com/wxapp.png',
     * ]
     */
    'wxapp' => [],
    /**
     * 支付宝小程序配置信息，按注释信息配置。不支持支付宝小程序则需设为空数组
     * [
     *      'name' => '示例支付宝小程序',
     *      'type' => '5',
     *      'app_id' => 'wxc375ef84a72b281f',
     *      'logo_url' => 'https://yuming.com/aliapp.png',
     * ]
     */
    'aliapp' => [],
    /**
     * 百度小程序配置信息，按注释信息配置。不支持百度小程序则需设为空数组
     * [
     *      'name' => '示例百度小程序',
     *      'type' => '6',
     *      'app_id' => 'wxc375ef84a72b281f',
     *      'app_key' => 'er75ef84a72w3b266q',
     *      'app_secret' => '1a1b124bd33940c3f50bafbf9646541d',
     *      'logo_url' => 'https://yuming.com/baiduapp.png',
     * ]
     */
    'baiduapp' => [],
    /**
     * 抖音小程序配置信息，按注释信息配置。不支持抖音小程序则需设为空数组
     * [
     *      'name' => '示例抖音小程序',
     *      'type' => '7',
     *      'app_id' => 'wxc375ef84a72b281f',
     *      'app_secret' => '1a1b124bd33940c3f50bafbf9646541d',
     *      'logo_url' => 'https://yuming.com/toutiaoapp.png',
     * ]
     */
    'toutiaoapp' => [],
];
