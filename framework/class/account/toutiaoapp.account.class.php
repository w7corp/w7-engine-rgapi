<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$.
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 微信平台公众号业务操作类.
 */
class ToutiaoappAccount extends WeAccount {
    protected $menuFrame = 'wxapp';
    protected $type = ACCOUNT_TYPE_TOUTIAOAPP_NORMAL;
    protected $typeName = '字节跳动小程序';
    protected $typeSign = TOUTIAOAPP_TYPE_SIGN;
    protected $supportVersion = STATUS_ON;

    protected function getAccountInfo($uniacid) {
        return table('account')->getByUniacid($uniacid);
    }
}
