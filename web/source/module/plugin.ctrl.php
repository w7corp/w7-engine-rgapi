<?php
/**
 * 应用插件
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('module');

$dos = array('display', 'module_shortcut');
$do = in_array($do, $dos) ? $do : 'display';

$module_name = safe_gpc_string($_GPC['module_name']);
$modulelist = uni_modules();
$modulelist = array_column($modulelist, null, 'name');
$module = $_W['current_module'] = $modulelist[$module_name];

if ('display' == $do) {
    $plugin_list = pdo_getall('modules_plugin', array('main_module' => $module_name), array('id', 'name'), 'name');

    $module_menu_plugin_list = table('core_menu_shortcut')->getCurrentModuleMenuPluginList($module_name);
    if (!empty($plugin_list)) {
        foreach ($plugin_list as $plugin_key => &$plugin_val) {
            if (empty($modulelist[$plugin_key])) {
                unset($plugin_list[$plugin_key]);
                continue;
            }
            if (!empty($plugin_val['uid']) && $plugin_val['uid'] != $_W['uid']) {
                unset($plugin_list[$plugin_key]);
                continue;
            }
            $plugin_val['plugin_info'] = module_fetch($plugin_val['name']);
            if (empty($plugin_val['plugin_info'])) {
                unset($plugin_list[$plugin_key]);
            }
            if (in_array($plugin_val['name'], array_keys($module_menu_plugin_list))) {
                $plugin_val['module_shortcut'] = 1;
            }
        }
    }

    template('module/plugin');
}

if ('module_shortcut' == $do) {
    global $_W;
    $status = safe_gpc_string($_GPC['module_shortcut']);
    $plugin_name = safe_gpc_string($_GPC['plugin_name']);

    $module_info = module_fetch($plugin_name);
    if (empty($module_info)) {
        itoast('模块不能被访问!', referer(), 'error');
    }
    $main_module_name = $module_info['main_module'];
    $position = 'module_' . $main_module_name . '_menu_plugin_shortcut';
    $plugin_shortcut = pdo_get('core_menu_shortcut', array('position' => $position, 'modulename' => $plugin_name, 'uniacid' => $_W['uniacid'], 'uid' => $_W['uid']));

    if (empty($plugin_shortcut)) {
        $data = array(
            'uid' => $_W['uid'],
            'uniacid' => $_W['uniacid'],
            'modulename' => $plugin_name,
            'position' => $position,
        );
        pdo_insert('core_menu_shortcut', $data);
    } else {
        pdo_delete('core_menu_shortcut', array('id' => $plugin_shortcut['id']));
    }
    cache_build_module_info($module_name);
    itoast('设置成功!', referer(), 'success');
}
