<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

class PhoneappAccount extends WeAccount {
    protected $menuFrame = 'wxapp';
    protected $type = ACCOUNT_TYPE_PHONEAPP_NORMAL;
    protected $typeSign = PHONEAPP_TYPE_SIGN;
    protected $typeName = 'APP';
    protected $supportVersion = STATUS_ON;

    protected function getAccountInfo($uniacid) {
        return table('account')->getByUniacid($uniacid);
    }
}
