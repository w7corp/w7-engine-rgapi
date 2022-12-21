<?php

namespace W7\Sdk\Module\Support;

class Account
{
    /** 号码类型 - 无 */
    public const TYPE_NONE = 0;

    /** 号码类型 - 公众号 */
    public const TYPE_WECHAT = 1;

    /** 号码类型 - 微信小程序 */
    public const TYPE_MINI_PROGRAM = 2;

    /** 号码类型 - APP */
    public const TYPE_APP = 4;

    /** 号码类型 - 支付宝小程序 */
    public const TYPE_ALI = 5;

    /** 号码类型 - 百度小程序 */
    public const TYPE_BAIDU = 6;

    /** 号码类型 - 字节跳动小程序 */
    public const TYPE_TOUTIAO = 7;

    /** 号码类型 - 企业微信 */
    public const TYPE_WORK = 8;
}