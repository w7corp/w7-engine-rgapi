<?php
/**
 * 验证规则.
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

!defined('CLOUD_API_DOMAIN') && define('CLOUD_API_DOMAIN', 'https://openapi.w7.cc');
!defined('V3_API_DOMAIN') && define('V3_API_DOMAIN', 'https://rgapi.w7.cc');
define('REGULAR_EMAIL', '/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/i');
define('REGULAR_MOBILE', '/1[3456789][0-9]{9}/');
define('REGULAR_USERNAME', '/^[\x{4e00}-\x{9fa5}a-z\d_\.]{1,30}$/iu');
/*
 * 模板引用相关
 */
//导入全局变量，并直接显示模板页内容。
define('TEMPLATE_DISPLAY', 0);
//导入全局变量，并返回模板页内容的字符串
define('TEMPLATE_FETCH', 1);
//返回模板编译文件的包含路径
define('TEMPLATE_INCLUDEPATH', 2);

//订阅号
define('ACCOUNT_SUBSCRIPTION', 1);
//订阅号-认证
define('ACCOUNT_SUBSCRIPTION_VERIFY', 3);
//服务号
define('ACCOUNT_SERVICE', 2);
//服务号-认证 认证媒体/政府订阅号
define('ACCOUNT_SERVICE_VERIFY', 4);
//正常接入公众号
define('ACCOUNT_TYPE_OFFCIAL_NORMAL', 1);
//正常接入小程序
define('ACCOUNT_TYPE_APP_NORMAL', 2);

//正常接入APP
define('ACCOUNT_TYPE_PHONEAPP_NORMAL', 4);
//支付宝小程序
define('ACCOUNT_TYPE_ALIAPP_NORMAL', 5);
//百度小程序
define('ACCOUNT_TYPE_BAIDUAPP_NORMAL', 6);
//字节跳动小程序
define('ACCOUNT_TYPE_TOUTIAOAPP_NORMAL', 7);
//正常接入PC
define('ACCOUNT_TYPE_WEBAPP_NORMAL', 12);
//正常接入系统首页
define('ACCOUNT_TYPE_WELCOMESYSTEM_NORMAL', 13);
//授权接入小程序
define('ACCOUNT_TYPE_APP_AUTH', 21);
//授权接入公众号
define('ACCOUNT_TYPE_OFFCIAL_AUTH', 22);


//公众号
define('ACCOUNT_TYPE_SIGN', 'account');
//小程序
define('WXAPP_TYPE_SIGN', 'wxapp');
//PC
define('WEBAPP_TYPE_SIGN', 'webapp');
//APP
define('PHONEAPP_TYPE_SIGN', 'phoneapp');
//欢迎页
define('WELCOMESYSTEM_TYPE_SIGN', 'welcome');
//支付宝小程序
define('ALIAPP_TYPE_SIGN', 'aliapp');
//百度小程序
define('BAIDUAPP_TYPE_SIGN', 'baiduapp');
//字节跳动小程序
define('TOUTIAOAPP_TYPE_SIGN', 'toutiaoapp');

//授权登录接入
define('ACCOUNT_OAUTH_LOGIN', 3);
//api接入
define('ACCOUNT_NORMAL_LOGIN', 1);

//店员操作
define('ACCOUNT_OPERATE_CLERK', 3);

define('ACCOUNT_MANAGE_NAME_FOUNDER', 'founder');
//系统卡券
define('SYSTEM_COUPON', 1);
//微信卡券
define('WECHAT_COUPON', 2);
//卡券类型
define('COUPON_TYPE_DISCOUNT', '1'); //折扣券
define('COUPON_TYPE_CASH', '2'); //代金券
define('COUPON_TYPE_GROUPON', '3'); //团购券
define('COUPON_TYPE_GIFT', '4'); //礼品券
define('COUPON_TYPE_GENERAL', '5'); //优惠券
define('COUPON_TYPE_MEMBER', '6'); //会员卡
define('COUPON_TYPE_SCENIC', '7'); //景点票
define('COUPON_TYPE_MOVIE', '8'); //电影票
define('COUPON_TYPE_BOARDINGPASS', '9'); //飞机票
define('COUPON_TYPE_MEETING', '10'); //会议票
define('COUPON_TYPE_BUS', '11'); //汽车票

define('ATTACH_OSS', 2); //远程附件类型：阿里云
define('ATTACH_QINIU', 3); //远程附件类型：七牛
define('ATTACH_COS', 4); //远程附件类型：腾讯云对象存储

define('ATTACH_TYPE_IMAGE', 1);
define('ATTACH_TYPE_VOICE', 2);
define('ATTACH_TYPE_VEDIO', 3);
define('ATTACH_TYPE_NEWS', 4);

define('ATTACHMENT_IMAGE', 'image');

define('ATTACH_SAVE_TYPE_FIXED', 1);
define('ATTACH_SAVE_TYPE_TEMP', 2);

define('STATUS_OFF', 0); //关闭状态
define('STATUS_ON', 1); //开启状态
define('STATUS_SUCCESS', 0); //ajax返回成功状态，增强语义

define('CACHE_EXPIRE_SHORT', 30);
define('CACHE_EXPIRE_MIDDLE', 300);
define('CACHE_EXPIRE_LONG', 3600);
define('CACHE_KEY_LENGTH', 100); //缓存键的最大长度

//非系统模块
//模块是否支持微信小程序
define('MODULE_SUPPORT_WXAPP', 2);
define('MODULE_NONSUPPORT_WXAPP', 1);
//模块是否支持公众号应用
define('MODULE_SUPPORT_ACCOUNT', 2);
define('MODULE_NONSUPPORT_ACCOUNT', 1);
//是否支持pc 1不支持  2支持
define('MODULE_NOSUPPORT_WEBAPP', 1);
define('MODULE_SUPPORT_WEBAPP', 2);
//是否支持app 1不支持  2支持
define('MODULE_NOSUPPORT_PHONEAPP', 1);
define('MODULE_SUPPORT_PHONEAPP', 2);
//是否支持系统首页 1不支持  2支持
define('MODULE_SUPPORT_SYSTEMWELCOME', 2);
define('MODULE_NONSUPPORT_SYSTEMWELCOME', 1);
//是否支持安卓 不支持1 支持2
define('MODULE_NOSUPPORT_ANDROID', 1);
define('MODULE_SUPPORT_ANDROID', 2);
//是否支持ios 不支持1 支持2
define('MODULE_NOSUPPORT_IOS', 1);
define('MODULE_SUPPORT_IOS', 2);
// 是否支持支付宝小程序 不支持1 支持2
define('MODULE_SUPPORT_ALIAPP', 2);
define('MODULE_NOSUPPORT_ALIAPP', 1);
// 是否支持百度小程序 不支持1 支持2
define('MODULE_SUPPORT_BAIDUAPP', 2);
define('MODULE_NOSUPPORT_BAIDUAPP', 1);
// 是否支持字节跳动小程序 不支持1 支持2
define('MODULE_SUPPORT_TOUTIAOAPP', 2);
define('MODULE_NOSUPPORT_TOUTIAOAPP', 1);

define('MODULE_SUPPORT_WXAPP_NAME', 'wxapp_support');
define('MODULE_SUPPORT_ACCOUNT_NAME', 'account_support');
define('MODULE_SUPPORT_WEBAPP_NAME', 'webapp_support');
define('MODULE_SUPPORT_PHONEAPP_NAME', 'phoneapp_support');
define('MODULE_SUPPORT_SYSTEMWELCOME_NAME', 'welcome_support');
define('MODULE_SUPPORT_ALIAPP_NAME', 'aliapp_support');
define('MODULE_SUPPORT_BAIDUAPP_NAME', 'baiduapp_support');
define('MODULE_SUPPORT_TOUTIAOAPP_NAME', 'toutiaoapp_support');

//微信支付类型
define('PAYMENT_WECHAT_TYPE_NORMAL', 1); //微信支付
define('PAYMENT_WECHAT_TYPE_BORROW', 2); //借用支付
define('PAYMENT_WECHAT_TYPE_SERVICE', 3); //服务商支付
define('PAYMENT_WECHAT_TYPE_CLOSE', 4);

define('MATERIAL_LOCAL', 'local'); //服务器素材类型
define('MATERIAL_WEXIN', 'perm'); //微信素材类型

//模块获取用户授权方式 1.静默授权 2.用户有感知授权
define('OAUTH_TYPE_BASE', 1);
define('OAUTH_TYPE_USERINFO', 2);
