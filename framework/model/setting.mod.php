<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 缓存设置信息
 * @param string $data 缓存数据
 * @param string $key 缓存键值
 * @return mixed
 */
function setting_save($data = '', $key = '') {
    if (empty($data) && empty($key)) {
        return error(-1, '参数不可为空。');
    }
    if (is_array($data) && empty($key)) {
        foreach ($data as $key => $value) {
            $record[] = "('$key', '" . iserializer($value) . "')";
        }
        if ($record) {
            $return = pdo_query("REPLACE INTO " . tablename('core_settings') . " (`key`, `value`) VALUES " . implode(',', $record));
        }
    } else {
        $return = pdo_insert('core_settings', array('key' => $key, 'value' => iserializer($data)), true);
    }
    $cachekey = cache_system_key('setting');
    cache_write($cachekey, '');
    return $return;
}

/**
 * 加载缓存的设置信息, 加载后也可以通过 $_W['setting'][$key] 中读取.
 * @param string $key
 * @return mixed
 */
function setting_load($key = '') {
    global $_W;

    $cachekey = cache_system_key('setting');
    $settings = cache_load($cachekey);
    if (empty($settings)) {
        $settings = pdo_getall('core_settings', array(), array(), 'key');
        if (is_array($settings)) {
            foreach ($settings as $k => &$v) {
                $settings[$k] = iunserializer($v['value']);
            }
        }
        cache_write($cachekey, $settings);
    }
    $_W['setting'] = empty($_W['setting']) ? array() : $_W['setting'];
    $_W['setting'] = array_merge($settings, (array)$_W['setting']);
    if (!empty($key)) {
        return array($key => empty($settings[$key]) ? '' : $settings[$key]);
    } else {
        return $settings;
    }
}

function setting_upgrade_version($version) {
    if (version_compare(IMS_VERSION, $version, '>=')) {
        return true;
    }
    $settings = [
        'version' => $version,
        'family' => 'c',
    ];
    return (bool)setting_save($settings, 'local_version_info');
}
