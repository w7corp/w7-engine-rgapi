<?php
/**
 * 公共数据
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

$system_roles = array('founder', 'vice_founder', 'owner', 'manager', 'operator', 'clerk', 'unbind_user', 'expired');
$user_defined_constants = get_defined_constants('true');
$user_defined_constants = $user_defined_constants['user'];

$common_info = array(
    'uid' => $_W['uid'],
    'submit_token' => $_W['token'],
    'siteroot' => $_W['siteroot'],
    'isfounder' => $_W['isfounder'],
    'system_roles' => $system_roles,
    'links' => array(),
    'uni_account_type' => $account_all_type,
    'uni_account_type_sign' => $account_all_type_sign,
    'defined_constants' => $user_defined_constants,
    'development' => $_W['config']['setting']['development'],
    'ishttps' => $_W['ishttps'],
);
iajax(0, $common_info);
