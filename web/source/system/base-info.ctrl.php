<?php
/**
 * 系统信息
 * [WeEngine System] Copyright (c) 2014 W7.CC.
*/
defined('IN_IA') or exit('Access Denied');
load()->model('system');

$dos = array('display', 'edit', 'check');
$do = in_array($do, $dos) ? $do : 'display';

$server_setting = $_W['setting']['server_setting'] ?? [];
if ('display' == $do) {
    if (empty($server_setting)) {
        $server_setting = array(
            'url' => $_W['siteroot'] . 'api.php',
            'app_id' => '',
            'app_secret' => '',
            'token' => random(32),
            'encodingaeskey' => random(43),
        );
        setting_save($server_setting, 'server_setting');
    }
    template('system/base-info');
}

if ('check' == $do) {
    if (empty($_W['setting']['server_setting']['app_id']) || empty($_W['setting']['server_setting']['app_secret'])) {
        iajax(-1, '请先配置app_id和app_secret。');
    }
    $result = uni_init_accounts();
    if (is_error($result)) {
        iajax(-1, $result['message']);
    }
    iajax(0, '接入成功！');
}

if ('edit' == $do) {
    $request_data = empty($_GPC['request_data']) ? '' : safe_gpc_string($_GPC['request_data']);
    switch (safe_gpc_string($_GPC['type'])) {
        case 'app_id':
            $server_setting['app_id'] = $request_data;
            break;
        case 'app_secret':
            $server_setting['app_secret'] = $request_data;
            break;
        case 'token':
            $server_setting['token'] = $request_data;
            break;
        case 'encodingaeskey':
            $server_setting['encodingaeskey'] = $request_data;
            break;
        default:
            iajax(-1, '参数错误！', referer());
    }

    $result = setting_save($server_setting, 'server_setting');
    if (!empty($server_setting['app_id']) && !empty($server_setting['app_secret'])) {
        $_W['setting']['server_setting']['app_id'] = $server_setting['app_id'];
        $_W['setting']['server_setting']['app_secret'] = $server_setting['app_secret'];
        $check = uni_init_accounts();
        if (is_error($check)) {
            iajax(-1, '自动接入检测失败，详情：' . $check['message']);
        }
    }
    if (is_error($result)) {
        iajax(-1, $result['message']);
    }
    iajax(0, '修改成功！', referer());
}
