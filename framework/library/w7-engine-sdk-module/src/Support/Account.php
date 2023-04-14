<?php

/**
 * WeEngine System
 *
 * (c) We7Team 2022 <https://www.w7.cc>
 *
 * This is not a free software
 * Using it under the license terms
 * visited https://www.w7.cc for more details
 */

namespace W7\Sdk\Module\Support;

class Account
{
    /** 号码类型 - 无 */
    public const TYPE_NONE = 0;

    /** 号码类型 - 公众号 */
    public const TYPE_WECHAT = 1;

    /** 号码类型 - 微信小程序 */
    public const TYPE_WECHAT_MINI = 2;

    /** 号码类型 - APP */
    public const TYPE_APP = 4;

    /** 号码类型 - 支付宝小程序 */
    public const TYPE_ALI_MINI = 5;

    /** 号码类型 - 百度小程序 */
    public const TYPE_BAIDU_MINI = 6;

    /** 号码类型 - 抖音小程序 */
    public const TYPE_TIK_TOK_MINI = 7;

    /** 号码类型 - 企业微信 */
    public const TYPE_WECHAT_WORK = 8;

    /** 号码类型 - 快手网页小程序 */
    public const TYPE_K_WAI_MINI = 9;

    /** 号码类型 - 微信视频号 */
    public const TYPE_WECHAT_EC = 10;

    /** 号码类型 - QQ小程序 */
    public const TYPE_QQ_MINI = 11;
}
