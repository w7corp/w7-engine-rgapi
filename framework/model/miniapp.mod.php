<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.w7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

/*
 * 获取小程序信息
 * @params int $uniacid
 * @params int $versionid
 * @return array
*/
function miniapp_fetch($uniacid, $version_id = '') {
    $version_id = max(0, intval($version_id));
    $miniapp_info = pdo_get('account', array('uniacid' => $uniacid));
    if (empty($miniapp_info)) {
        return $miniapp_info;
    }
    $account_extra_info = uni_account_type($miniapp_info['type']);
    if (empty($version_id)) {
        $sql = 'SELECT * FROM ' . tablename($account_extra_info['version_tablename']) . ' WHERE `uniacid`=:uniacid ORDER BY `id` DESC';
        $miniapp_version_info = pdo_fetch($sql, array(':uniacid' => $uniacid));
    } else {
        $miniapp_version_info = pdo_get($account_extra_info['version_tablename'], array('id' => $version_id, 'uniacid' => $uniacid));
    }
    $miniapp_version_info['modules'] = iunserializer($miniapp_version_info['modules']);
    $miniapp_info['version'] = $miniapp_version_info;
    $miniapp_info['version_num'] = empty($miniapp_version_info['version']) ? array() : explode('.', $miniapp_version_info['version']);
    
    return  $miniapp_info;
}

/**
 * 获取小程序单个版本.
 * @param int $version_id
 */
function miniapp_version($version_id) {
    $version_info = array();
    $version_id = intval($version_id);
    if (empty($version_id)) {
        return $version_info;
    }
    //需包含对象的类的定义，否则在解序列化对象的时候，报错__PHP_Incomplete_Class_Name
    load()->classs('wxapp.account');
    $cachekey = cache_system_key('miniapp_version', array('version_id' => $version_id));
    $cache = cache_load($cachekey);
    if (!empty($cache)) {
        return $cache;
    }
    $version_info = table('wxapp_versions')->getById($version_id);
    $version_info = table('wxapp_versions')->dataunserializer($version_info);
    $version_info = miniapp_version_detail_info($version_info);
    cache_write($cachekey, $version_info);
    
    return $version_info;
}

function miniapp_version_detail_info($version_info) {
    if (empty($version_info) || empty($version_info['uniacid']) || empty($version_info['modules'])) {
        return $version_info;
    }

    $account = pdo_get('account', array('uniacid' => $version_info['uniacid']));
    if (in_array($account['type'], array(ACCOUNT_TYPE_APP_NORMAL, ACCOUNT_TYPE_APP_AUTH))) {
        foreach ($version_info['modules'] as $i => $module) {
            $module_info = module_fetch($module['name']);
            $module_info['version'] = $module['version'];
            $module['uniacid'] = table('uni_link_uniacid')->getMainUniacid($version_info['uniacid'], $module['name'], $version_info['id']);
            if (!empty($module['uniacid'])) {
                $module_info['uniacid'] = $module['uniacid'];
                $link_account = uni_fetch($module['uniacid']);
                $module_info['account'] = $link_account->account;
                $module_info['account']['logo'] = $link_account->logo;
            }
            //模块默认入口
            $module_info['cover_entrys'] = module_entries($module['name'], array('cover'));
            $module_info['defaultentry'] = empty($module['defaultentry']) ? '' : $module['defaultentry'];
            $version_info['modules'][$i] = $module_info;
        }
        if (count($version_info['modules']) > 0) {
            $version_module = current($version_info['modules']);
            $version_info['cover_entrys'] = !empty($version_module['cover_entrys']['cover']) ? $version_module['cover_entrys']['cover'] : array();
        }
        $version_info['support_live'] = strpos($version_info['default_appjson'], 'wx2b03c6e691cd7370') !== false ? 1 : 0;
    } else {
        foreach ($version_info['modules'] as $i => $module) {
            $module_info = module_fetch($module['name']);
            $module_info['version'] = $module['version'];
            $module['uniacid'] = table('uni_link_uniacid')->getMainUniacid($version_info['uniacid'], $module['name'], $version_info['id']);
            if (!empty($module['uniacid'])) {
                $module_info['uniacid'] = $module['uniacid'];
                $link_account = uni_fetch($module['uniacid']);
                $module_info['account'] = $link_account->account;
                $module_info['account']['logo'] = $link_account->logo;
            }
            $version_info['modules'][$i] = $module_info;
        }
    }
    return $version_info;
}

/**
 * 根据版本号获取当前小程序版本信息.
 * @param mixed $version
 * @return array()
 */
function miniapp_version_by_version($version) {
    global $_W;
    $version_info = array();
    $version = trim($version);
    if (empty($version)) {
        return $version_info;
    }
    $version_info = table('wxapp_versions')->getByUniacidAndVersion($_W['uniacid'], $version);
    $version_info = miniapp_version_detail_info($version_info);

    return $version_info;
}
