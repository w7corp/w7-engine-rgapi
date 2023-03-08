<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
load()->func('communication');

$step = $_GPC['step'] ?? '';
$steps = array('scripts', 'module_upgrade');
$step = in_array($step, $steps) ? $step : '';

$upgrade = glob(IA_ROOT . '/upgrade/*');
$result = [];
if (!empty($upgrade)) {
    foreach ($upgrade as $item) {
        $path_array = explode('/', $item);
        $version = end($path_array);
        if (!str_is_version($version)) {
            continue;
        }
        if (version_compare($version, IMS_VERSION, '<=')) {
            continue;
        }
        include_once $item . '/up.php';
        $class_name = 'W7\\U' . str_replace('.', '', $version) . '\\Up';
        $result[] = array('version' => $version, 'class' => $class_name, 'module_name' => '', 'description' => $class_name::DESCRIPTION, 'type' => 'system');
    }
}
if ('scripts' == $step && $_W['ispost']) {
    $version = trim($_GPC['version']);
    $result = array_column($result, null, 'version');
    if (class_exists($result[$version]['class'])) {
        set_time_limit(0);
        $up_class = new $result[$version]['class']();
        if ($up_class->up()) {
            cache_build_setting();
            setting_upgrade_version($version);
            cache_delete(cache_system_key('checkupgrade'));
            exit('success');
        }
    }
    exit('failed');
}
$modules = pdo_getall('modules', [], ['name', 'title', 'version'], 'name');
foreach ($modules as $module_name => $module) {
    $root = IA_ROOT . '/addons/' . $module_name;
    $filename = $root . '/manifest.xml';
    if (!file_exists($filename)) {
        continue;
    }
    $xml = file_get_contents($filename);
    $xml = ext_module_manifest_parse($xml);
    $version = !empty($xml['application']['version']) ? $xml['application']['version'] : '1.0.0';
    if (version_compare($version, $module['version'], '<=')) {
        continue;
    }
    $result[] = ['version' => $version, 'module_name' => $module_name, 'description' => '应用“' . $module['title'] . '”升级', 'type' => 'module'];
}
if ('module_upgrade' == $step && $_W['ispost']) {
    $module_name = safe_gpc_string($_GPC['module_name']);
    //判断模块相关配置和文件是否合法
    $manifest = ext_module_manifest($module_name);
    $check_manifest_result = ext_manifest_check($module_name, $manifest);
    if (is_error($check_manifest_result)) {
        itoast($check_manifest_result['message'], '', 'error');
    }

    $check_file_result = ext_file_check($module_name, $manifest);
    if (is_error($check_file_result)) {
        itoast($check_file_result['message'], '', 'error');
    }

    if (!empty($manifest['platform']['plugin_list'])) {
        pdo_delete('modules_plugin', array('main_module' => $manifest['application']['identifie']));
        foreach ($manifest['platform']['plugin_list'] as $plugin) {
            pdo_insert('modules_plugin', array('main_module' => $manifest['application']['identifie'], 'name' => $plugin));
        }
    }

    $module_upgrade = ext_module_convert($manifest);
    unset($module_upgrade['name'], $module_upgrade['title'], $module_upgrade['ability'], $module_upgrade['description']);

    //处理模块菜单
    $points = ext_module_bindings();
    $bindings = array_elements(array_keys($points), $module_upgrade, false);
    foreach ($points as $point_name => $point_info) {
        unset($module_upgrade[$point_name]);
        if (is_array($bindings[$point_name]) && !empty($bindings[$point_name])) {
            foreach ($bindings[$point_name] as $entry) {
                $entry['module'] = $manifest['application']['identifie'];
                $entry['entry'] = $point_name;
                if ('page' == $point_name && !empty($wxapp_support)) {
                    $entry['url'] = $entry['do'];
                    $entry['do'] = '';
                }
                if ($entry['title'] && $entry['do']) {
                    //保存xml里面包含的do,最后删除数据库中废弃的do
                    $not_delete_do[] = $entry['do'];
                    $module_binding = table('modules_bindings')->getByEntryDo($module_name, $point_name, $entry['do']);
                    if (!empty($module_binding)) {
                        pdo_update('modules_bindings', $entry, array('eid' => $module_binding['eid']));
                        continue;
                    }
                } elseif ($entry['call']) {
                    $not_delete_call[] = $entry['call'];
                    $module_binding = table('modules_bindings')->getByEntryCall($module_name, $point_name, $entry['call']);
                    if (!empty($module_binding)) {
                        pdo_update('modules_bindings', $entry, array('eid' => $module_binding['eid']));
                        continue;
                    }
                }
                pdo_insert('modules_bindings', $entry);
            }
            //删除废弃的do
            $modules_bindings_table = table('modules_bindings');
            $modules_bindings_table
                ->searchWithModuleEntry($manifest['application']['identifie'], $point_name)
                ->where('call', '')
                ->where('do !=', empty($not_delete_do) ? '' : $not_delete_do)
                ->delete();
            //删除废弃的call
            $modules_bindings_table
                ->searchWithModuleEntry($manifest['application']['identifie'], $point_name)
                ->where('do', '')
                ->where('title', '')
                ->where('call !=', empty($not_delete_call) ? '' : $not_delete_call)
                ->delete();
            unset($not_delete_do, $not_delete_call);
        } else {
            table('modules_bindings')->searchWithModuleEntry($manifest['application']['identifie'], $point_name)->delete();
        }
    }
    ext_module_run_script($manifest, 'upgrade');

    $module_upgrade['permissions'] = iserializer($module_upgrade['permissions']);
    $module_upgrade['settings'] = empty($manifest['application']['setting']) ? STATUS_OFF : STATUS_ON;
    pdo_update('modules', $module_upgrade, array('name' => $module_name));

    if (!empty($module_upgrade['subscribes'])) {
        ext_check_module_subscribe($module_name);
    }
    cache_build_module_info($module_name);
    cache_delete(cache_system_key('checkupgrade'));
    exit('success');
}
if (empty($result)) {
    cache_updatecache();
    if (ini_get('opcache.enable') || ini_get('opcache.enable_cli')) {
        opcache_reset();
    }
    itoast('', url('module/display'), 'success');
}
template('cloud/process');
