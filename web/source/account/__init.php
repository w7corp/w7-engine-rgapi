<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
define('FRAME', '');
$account_all_type_sign = uni_account_type_sign();
$account_param = WeAccount::create(array('type' => empty($_GPC['account_type']) ? '' : $_GPC['account_type']));
if (!is_error($account_param)) {
    define('ACCOUNT_TYPE', $account_param->type);
    define('ACCOUNT_TYPE_NAME', $account_param->typeName);
}
