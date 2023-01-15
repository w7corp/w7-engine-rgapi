<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

if (in_array($action, array('manage-account', 'welcome', 'link-account', 'shortcut', 'plugin'))) {
    //模块内定死使用account
    define('FRAME', 'account');
} else {
    define('FRAME', '');
}
if (in_array($action, array('manage-account', 'welcome', 'plugin'))) {
    define('IN_MODULE', $_GPC['module_name'] ?: $_GPC['m']);
}
