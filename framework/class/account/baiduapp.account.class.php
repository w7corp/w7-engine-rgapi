<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$.
 */
defined('IN_IA') or exit('Access Denied');

class BaiduappAccount extends WeAccount {
    protected $menuFrame = 'wxapp';
    protected $type = ACCOUNT_TYPE_BAIDUAPP_NORMAL;
    protected $typeName = '百度小程序';
    protected $typeSign = BAIDUAPP_TYPE_SIGN;
    protected $supportVersion = STATUS_ON;

    protected function getAccountInfo($uniacid) {
        return table('account')->getByUniacid($uniacid);
    }
}
