<?php
/**
 * 公众号列表
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('miniapp');
load()->model('phoneapp');

$dos = array('rank', 'display', 'list', 'switch', 'platform', 'setting_star',
            'account_num', 'welcome_link', 'account_modules', 'account_create_info');
$do = in_array($_GPC['do'], $dos) ? $do : 'platform';

if ('platform' == $do) {
    $url = home_url();
    $last_uniacid = switch_get_account_display();
    if (empty($last_uniacid)) {
        itoast('', $url, 'info');
    }
    if (!empty($last_uniacid) && $last_uniacid != $_W['uniacid']) {
        switch_save_account_display($last_uniacid);
    }
    $permission = permission_account_user_role($_W['uid'], $last_uniacid);
    if (empty($permission)) {
        itoast('', $url, 'info');
    }
    $account_info = uni_fetch($last_uniacid);

    if (ACCOUNT_TYPE_SIGN == $account_info['type_sign']) {
        $url = url('home/welcome/platform');
    } elseif (WEBAPP_TYPE_SIGN == $account_info['type_sign']) {
        $url = url('webapp/home/display');
    } else {
        $last_version = miniapp_fetch($last_uniacid);
        if (!empty($last_version)) {
            $url = url('miniapp/version/home', array('version_id' => $last_version['version']['id']));
        }
    }
    itoast('', $url);
}
//切换平台账号
if ('switch' == $do) {
    $uniacid = intval($_GPC['uniacid']);
    $module_name = safe_gpc_string($_GPC['module_name']);
    if (!empty($uniacid)) {
        $account_info = uni_fetch($uniacid);
        $type = $account_info['type'];

        if (STATUS_ON != $account_info->supportVersion) {
            if (empty($module_name)) {
                $url = url('home/welcome/platform');
                if (ACCOUNT_TYPE_WEBAPP_NORMAL == $type) {
                    $url = url('webapp/home/display');
                }
            } else {
                $url = url('home/welcome/ext', array('m' => $module_name));
                $main_uniacid = table('uni_link_uniacid')->getMainUniacid($uniacid, $module_name);
                if (!empty($main_uniacid)) {
                    $uniacid = $main_uniacid;
                    $account_info = uni_fetch($main_uniacid);
                }
            }
        } else {
            if (!empty($module_name)) {
                $url = url('home/welcome/ext/', array('m' => $module_name));
                $main_uniacid = table('uni_link_uniacid')->getMainUniacid($uniacid, $module_name);
                if (!empty($main_uniacid)) {
                    $uniacid = $main_uniacid;
                    $account_info = uni_fetch($main_uniacid);
                }
            } else {
                $url = url('miniapp/version/home');
            }
        }
        $url .= '&uniacid=' . $uniacid;
        if (!empty($_GPC['redirect'])) {
            $url = safe_gpc_url($_GPC['redirect']);
        }
        itoast('', $url);
    }
}
