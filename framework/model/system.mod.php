<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

function system_menu() {
    global $w7_system_menu;
    require_once IA_ROOT . '/web/common/frames.inc.php';
    return $w7_system_menu;
}

/**
 * 获取包括系统及模块所有的菜单权限
 */
function system_menu_permission_list($role = '') {
    global $_W;
    $system_menu = cache_load(cache_system_key('system_frame', array('uniacid' => $_W['uniacid'])));
    if (empty($system_menu)) {
        cache_build_frame_menu();
        $system_menu = cache_load(cache_system_key('system_frame', array('uniacid' => $_W['uniacid'])));
    }
    return $system_menu;
}

/**
 * 检测站点 php 拓展是否开启
 * @param $extension
 * @return bool
 */
function system_check_php_ext($extension) {
    return extension_loaded($extension) ? true : false;
}

/**
 * 获取站点设置可修改的项
 * @return array
 */
function system_setting_items() {
    return array(
        'logo',
        'icon',
        'log_status',
        'site_name',
        'cloud_status'
    );
}
