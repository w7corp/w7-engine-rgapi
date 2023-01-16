<?php
/**
 * 模块管理
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('extension');
load()->model('cache');
load()->model('module');
load()->model('utility');
load()->func('db');
$dos = array('install', 'uninstall');
$do = in_array($do, $dos) ? $do : 'install';

if ('install' == $do) {
    $modules = [];
    $addons = glob(IA_ROOT . '/addons/*');
    foreach ($addons as $item) {
        if (is_file($item)) {
            continue;
        }
        $modules[] = basename($item);
    }
    asort($modules);
    $main_module = current($modules);
    foreach ($modules as $key => $item) {
        if (0 !== strpos($item, $main_module)) {
            unset($modules[$key]);
        }
    }
    foreach ($modules as $module_name) {
        $installed_module = table('modules')->getByName($module_name);
        if (!empty($installed_module)) {
            continue;
        }
        $manifest = ext_module_manifest($module_name);
        $module = ext_module_convert($manifest);
        if (!empty($manifest['platform']['main_module'])) {
            $main_module_fetch = module_fetch($manifest['platform']['main_module']);
            if (empty($main_module_fetch)) {
                itoast('请先安装主模块后再安装插件');
            }
            $plugin_exist = table('modules_plugin')->getPluginExists($manifest['platform']['main_module'], $manifest['application']['identifie']);
            if (empty($plugin_exist)) {
                pdo_insert('modules_plugin', array('main_module' => $manifest['platform']['main_module'], 'name' => $manifest['application']['identifie']));
            }
        }
    
        $check_manifest_result = ext_manifest_check($module_name, $manifest);
        if (is_error($check_manifest_result)) {
            itoast($check_manifest_result['message'], '', 'error');
        }
        $check_file_result = ext_file_check($module_name, $manifest);
        if (is_error($check_file_result)) {
            itoast('模块缺失文件，请检查模块文件中site.php, processor.php, module.php, receiver.php 文件是否存在！', '', 'error');
        }
    
        $module['logo'] = 'addons/' . $module['name'] . '/icon.jpg';
        if (!empty($manifest['platform']['plugin_list'])) {
            foreach ($manifest['platform']['plugin_list'] as $plugin) {
                pdo_insert('modules_plugin', array('main_module' => $manifest['application']['identifie'], 'name' => $plugin));
            }
        }
        $points = ext_module_bindings();
        if (!empty($points)) {
            $bindings = array_elements(array_keys($points), $module, false);
            table('modules_bindings')->deleteByName($manifest['application']['identifie']);
            foreach ($points as $name => $point) {
                unset($module[$name]);
                if (is_array($bindings[$name]) && !empty($bindings[$name])) {
                    foreach ($bindings[$name] as $entry) {
                        $entry['module'] = $manifest['application']['identifie'];
                        $entry['entry'] = $name;
                        if ('page' == $name && !empty($wxapp_support)) {
                            $entry['url'] = $entry['do'];
                            $entry['do'] = '';
                        }
                        table('modules_bindings')->fill($entry)->save();
                    }
                }
            }
        }
    
        $module['permissions'] = iserializer($module['permissions']);
        $module['settings'] = empty($manifest['application']['setting']) ? STATUS_OFF : STATUS_ON;
    
        ext_module_run_script($manifest, 'install');
        $module['title_initial'] = get_first_pinyin($module['title']);
        $module['createtime'] = TIMESTAMP;
        pdo_insert('modules', $module);
    }
    cache_build_module_subscribe_type();
    setting_save('1', 'modules_inited');
    $upgrade = glob(IA_ROOT . '/upgrade/*');
    if (!empty($upgrade)) {
        $init_version = '1.0.0';
        foreach ($upgrade as $item) {
            $path_array = explode('/', $item);
            $version = end($path_array);
            if (!str_is_version($version)) {
                continue;
            }
            if (version_compare($version, $init_version, '<=')) {
                continue;
            }
            $init_version = $version;
        }
        setting_save($init_version, 'local_version');
    }
    itoast('所有模块安装成功！', url('module/display'), 'success');
}

//卸载模块
if ('uninstall' == $do) {
    $name = safe_gpc_string($_GPC['module_name']);

    $module = module_fetch($name);
    if (empty($module)) {
        itoast('应用不存在或是已经卸载！');
    }

    ext_module_clean($name);
    ext_execute_uninstall_script($name);
    cache_build_module_subscribe_type();
    
    cache_build_module_info($name);
    itoast('卸载成功！', url('module/display/display'), 'success');
}
