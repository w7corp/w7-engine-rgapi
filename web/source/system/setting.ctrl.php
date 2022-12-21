<?php
/**
 * 站点相关操作
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
load()->model('system');

$dos = array('basic', 'save_setting');
$do = in_array($do, $dos) ? $do : 'basic';
$settings = $_W['setting']['copyright'] ?? [];

if (empty($settings) || !is_array($settings)) {
    $settings = array(
        'icon' => '',
        'logo' => '',
        'site_name' => '',
    );
}
if ('basic' == $do) {
    $settings['icon'] = to_global_media($settings['icon']);
    $settings['logo'] = to_global_media($settings['logo']);
    template('system/setting');
}

if ('save_setting' == $do) {
    $system_setting_items = system_setting_items();
    $key = safe_gpc_string($_GPC['key']);

    switch ($key) {
        case 'icon':
        case 'logo':
            $settings[$key] = safe_gpc_url($_GPC['value'], false);
            break;
        default:
            if (1 == intval($_GPC['is_int'])) {
                $settings[$key] = intval($_GPC['value']);
            } else {
                $settings[$key] = safe_gpc_string($_GPC['value']);
                if (!empty($_GPC['value']) && empty($settings[$key])) {
                    iajax(-1, '提交的参数不合法！');
                }
            }
            break;
    }

    if (!in_array($key, $system_setting_items)) {
        iajax(-1, '参数错误！', url('system/setting'));
    }
    setting_save($settings, 'copyright');

    iajax(0, '更新设置成功！', referer());
}
