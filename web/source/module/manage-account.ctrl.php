<?php
/**
 * 设置模块启用停用，并显示模块到快捷菜单中.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

load()->model('module');
load()->model('account');
load()->model('user');
load()->model('cloud');
load()->model('cache');
load()->model('extension');

$dos = array('setting');
$do = in_array($do, $dos) ? $do : 'setting';
if ('setting' == $do) {
    $module_name = safe_gpc_string($_GPC['module_name']) ? safe_gpc_string($_GPC['module_name']) : safe_gpc_string($_GPC['m']);
    $module = $_W['current_module'] = module_fetch($module_name);
    if (empty($module)) {
        itoast('抱歉，你操作的模块不能被访问！', '', '');
    }

    if (!defined('IN_MODULE')) {
        define('IN_MODULE', $module_name);
    }
    // 兼容历史性问题：模块内获取不到模块信息$module的问题
    define('CRUMBS_NAV', 1);
    $obj = WeUtility::createModule($module['name']);
    $obj->settingsDisplay([]);
    exit();
}
