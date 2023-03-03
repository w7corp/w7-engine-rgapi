<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 平台所有账号类型附属信息(根据type_sign即账号标识分类)
 * @param string $type_sign 账号标识
 * @return array|mixed
 */
function uni_account_type_sign($type_sign = '') {
    $all_account_type_sign = array(
        ACCOUNT_TYPE_SIGN => array(
            'contain_type' => array(ACCOUNT_TYPE_OFFCIAL_NORMAL, ACCOUNT_TYPE_OFFCIAL_AUTH),
            'level' => array(ACCOUNT_SUBSCRIPTION => '订阅号', ACCOUNT_SERVICE => '服务号', ACCOUNT_SUBSCRIPTION_VERIFY => '认证订阅号', ACCOUNT_SERVICE_VERIFY => '认证服务号'),
            'jointype' => array(ACCOUNT_TYPE_OFFCIAL_NORMAL => '普通接入', ACCOUNT_TYPE_OFFCIAL_AUTH => '授权接入'),
            'icon' => 'wi wi-wx-circle',
            'createurl' => url('account/post-step'),
            'title' => '公众号',
        ),
        WXAPP_TYPE_SIGN => array(
            'contain_type' => array(ACCOUNT_TYPE_APP_NORMAL, ACCOUNT_TYPE_APP_AUTH),
            'level' => array(),
            'jointype' => array(ACCOUNT_TYPE_APP_NORMAL => '普通接入', ACCOUNT_TYPE_APP_AUTH => '授权接入'),
            'icon' => 'wi wi-wxapp',
            'createurl' => url('wxapp/post/design_method'),
            'title' => '微信小程序',
        ),
        WEBAPP_TYPE_SIGN => array(
            'contain_type' => array(ACCOUNT_TYPE_WEBAPP_NORMAL),
            'level' => array(),
            'icon' => 'wi wi-pc-circle',
            'createurl' => url('account/create', array('sign' => 'webapp')),
            'title' => 'PC',
        ),
        PHONEAPP_TYPE_SIGN => array(
            'contain_type' => array(ACCOUNT_TYPE_PHONEAPP_NORMAL),
            'level' => array(),
            'icon' => 'wi wi-app',
            'createurl' => url('account/create', array('sign' => 'phoneapp')),
            'title' => 'APP',
        ),
        ALIAPP_TYPE_SIGN => array(
            'contain_type' => array(ACCOUNT_TYPE_ALIAPP_NORMAL),
            'level' => array(),
            'icon' => 'wi wi-aliapp',
            'createurl' => url('account/create', array('sign' => 'aliapp')),
            'title' => '支付宝小程序',
        ),
        BAIDUAPP_TYPE_SIGN => array(
            'contain_type' => array(ACCOUNT_TYPE_BAIDUAPP_NORMAL),
            'level' => array(),
            'icon' => 'wi wi-baiduapp',
            'createurl' => url('account/create', array('sign' => 'baiduapp')),
            'title' => '百度小程序',
        ),
        TOUTIAOAPP_TYPE_SIGN => array(
            'contain_type' => array(ACCOUNT_TYPE_TOUTIAOAPP_NORMAL),
            'level' => array(),
            'icon' => 'wi wi-toutiaoapp',
            'createurl' => url('account/create', array('sign' => 'toutiaoapp')),
            'title' => '字节跳动小程序',
        ),
    );
    if (!empty($type_sign)) {
        return !empty($all_account_type_sign[$type_sign]) ? $all_account_type_sign[$type_sign] : array();
    }
    return $all_account_type_sign;
}

/**
 * 平台所有账号类型附属信息(根据type即账号类型分类)
 * @param int $type 账号类型
 * @return array|mixed
 */
function uni_account_type($type = 0) {
    $all_account_type = array(
        ACCOUNT_TYPE_OFFCIAL_NORMAL => array(
            'title' => '公众号',
            'type_sign' => ACCOUNT_TYPE_SIGN,
            'module_support_name' => MODULE_SUPPORT_ACCOUNT_NAME,
            'module_support_value' => MODULE_SUPPORT_ACCOUNT,
        ),
        ACCOUNT_TYPE_OFFCIAL_AUTH => array(
            'title' => '公众号',
            'type_sign' => ACCOUNT_TYPE_SIGN,
            'module_support_name' => MODULE_SUPPORT_ACCOUNT_NAME,
            'module_support_value' => MODULE_SUPPORT_ACCOUNT,
        ),
        ACCOUNT_TYPE_APP_NORMAL => array(
            'title' => '微信小程序',
            'type_sign' => WXAPP_TYPE_SIGN,
            'support_version' => 1,
            'version_tablename' => 'wxapp_versions',
            'module_support_name' => MODULE_SUPPORT_WXAPP_NAME,
            'module_support_value' => MODULE_SUPPORT_WXAPP,
        ),
        ACCOUNT_TYPE_APP_AUTH => array(
            'title' => '微信小程序',
            'type_sign' => WXAPP_TYPE_SIGN,
            'support_version' => 1,
            'version_tablename' => 'wxapp_versions',
            'module_support_name' => MODULE_SUPPORT_WXAPP_NAME,
            'module_support_value' => MODULE_SUPPORT_WXAPP,
        ),
        ACCOUNT_TYPE_WEBAPP_NORMAL => array(
            'title' => 'PC',
            'type_sign' => WEBAPP_TYPE_SIGN,
            'module_support_name' => MODULE_SUPPORT_WEBAPP_NAME,
            'module_support_value' => MODULE_SUPPORT_WEBAPP,
        ),
        ACCOUNT_TYPE_PHONEAPP_NORMAL => array(
            'title' => 'APP',
            'type_sign' => PHONEAPP_TYPE_SIGN,
            'support_version' => 1,
            'version_tablename' => 'wxapp_versions',
            'module_support_name' => MODULE_SUPPORT_PHONEAPP_NAME,
            'module_support_value' => MODULE_SUPPORT_PHONEAPP,
        ),
        ACCOUNT_TYPE_ALIAPP_NORMAL => array(
            'title' => '支付宝小程序',
            'type_sign' => ALIAPP_TYPE_SIGN,
            'support_version' => 1,
            'version_tablename' => 'wxapp_versions',
            'module_support_name' => MODULE_SUPPORT_ALIAPP_NAME,
            'module_support_value' => MODULE_SUPPORT_ALIAPP,
        ),
        ACCOUNT_TYPE_BAIDUAPP_NORMAL => array(
            'title' => '百度小程序',
            'type_sign' => BAIDUAPP_TYPE_SIGN,
            'support_version' => 1,
            'version_tablename' => 'wxapp_versions',
            'module_support_name' => MODULE_SUPPORT_BAIDUAPP_NAME,
            'module_support_value' => MODULE_SUPPORT_BAIDUAPP,
        ),
        ACCOUNT_TYPE_TOUTIAOAPP_NORMAL => array(
            'title' => '字节跳动小程序',
            'type_sign' => TOUTIAOAPP_TYPE_SIGN,
            'support_version' => 1,
            'version_tablename' => 'wxapp_versions',
            'module_support_name' => MODULE_SUPPORT_TOUTIAOAPP_NAME,
            'module_support_value' => MODULE_SUPPORT_TOUTIAOAPP,
        ),
    );
    if (!empty($type)) {
        return !empty($all_account_type[$type]) ? $all_account_type[$type] : array();
    }
    return $all_account_type;
}

/**
 * 获取指定统一公号下默认子号的的信息
 * @param int $uniacid 公众号ID
 * @return array 当前公众号信息
 */
function uni_fetch($uniacid = 0) {
    global $_W;
    $uniacid = empty($uniacid) ? $_W['uniacid'] : intval($uniacid);
    $account_api = WeAccount::createByUniacid($uniacid);
    if (is_error($account_api)) {
        return $account_api;
    }
    $account_api->__toArray();
    return $account_api;
}

/**
 * 获取指定公号下所有安装模块及模块信息
 * 公众号的权限是owner所有套餐内的全部模块权限
 * @param int $uniacid 公众号id
 * @return array 模块列表
 */
function uni_modules_by_uniacid($uniacid) {
    load()->model('user');
    load()->model('module');
    $account_info = table('account')->getByUniacid($uniacid);
    $uni_account_type = uni_account_type($account_info['type']);
    $owner_uid = pdo_getall('uni_account_users', array('uniacid' => $uniacid, 'role' => array('owner', 'vice_founder')), array('uid', 'role'), 'role');
    $owner_uid = !empty($owner_uid['owner']) ? $owner_uid['owner']['uid'] : (!empty($owner_uid['vice_founder']) ? $owner_uid['vice_founder']['uid'] : 0);

    $cachekey = cache_system_key('unimodules', array('uniacid' => $uniacid));
    $modules = cache_load($cachekey);
    if (empty($modules)) {
        $enabled_modules = table('modules')->getall();
        if (!empty($owner_uid) && !user_is_founder($owner_uid, true)) {
            //设置的公众号应用权限和商城购买的应用权限
            $group_modules = table('account')->accountGroupModules($uniacid);
            //公众号owner的权限
            $user_modules = user_modules($owner_uid);
            if (!empty($user_modules)) {
                $group_modules = array_unique(array_merge($group_modules, array_keys($user_modules)));
                $group_modules = array_intersect(array_keys($enabled_modules), $group_modules);
            }
        } else {
            $group_modules = array_keys($enabled_modules);
        }
        cache_write($cachekey, $group_modules);
        $modules = $group_modules;
    }
    $modules = array_merge($modules);

    $module_list = array();
    if (!empty($modules)) {
        foreach ($modules as $name) {
            if (empty($name)) {
                continue;
            }
            $module_info = module_fetch($name);
            $module_info[$uni_account_type['module_support_name']] = empty($module_info[$uni_account_type['module_support_name']]) ? '' : $module_info[$uni_account_type['module_support_name']];
            if ($module_info[$uni_account_type['module_support_name']] != $uni_account_type['module_support_value']) {
                continue;
            }
            //将模块停用删除支持设置为不支持
            if (!empty($module_info['recycle_info'])) {
                foreach (module_support_type() as $support => $value) {
                    if ($module_info['recycle_info'][$support] > 0 && $module_info[$support] == $value['support']) {
                        $module_info[$support] = $value['not_support'];
                    }
                }
            }
            //不支持当前account类型或仅支持系统首页的模块直接continue
            if ($module_info[MODULE_SUPPORT_ACCOUNT_NAME] != MODULE_SUPPORT_ACCOUNT &&
                in_array($account_info['type'], array(ACCOUNT_TYPE_OFFCIAL_NORMAL, ACCOUNT_TYPE_OFFCIAL_AUTH))) {
                continue;
            }
            if ($module_info[MODULE_SUPPORT_WEBAPP_NAME] != MODULE_SUPPORT_WEBAPP &&
                in_array($account_info['type'], array(ACCOUNT_TYPE_WEBAPP_NORMAL))) {
                continue;
            }
            if ($module_info[MODULE_SUPPORT_PHONEAPP_NAME] != MODULE_SUPPORT_PHONEAPP &&
                in_array($account_info['type'], array(ACCOUNT_TYPE_PHONEAPP_NORMAL))) {
                continue;
            }
            if ($module_info[MODULE_SUPPORT_ALIAPP_NAME] != MODULE_SUPPORT_ALIAPP &&
                in_array($account_info['type'], array(ACCOUNT_TYPE_ALIAPP_NORMAL))) {
                continue;
            }
            if ($module_info[MODULE_SUPPORT_BAIDUAPP_NAME] != MODULE_SUPPORT_BAIDUAPP &&
                in_array($account_info['type'], array(ACCOUNT_TYPE_BAIDUAPP_NORMAL))) {
                continue;
            }
            if ($module_info[MODULE_SUPPORT_TOUTIAOAPP_NAME] != MODULE_SUPPORT_TOUTIAOAPP &&
                in_array($account_info['type'], array(ACCOUNT_TYPE_TOUTIAOAPP_NORMAL))) {
                continue;
            }
            if ($module_info[MODULE_SUPPORT_WXAPP_NAME] != MODULE_SUPPORT_WXAPP &&
                $module_info[MODULE_SUPPORT_ACCOUNT_NAME] != MODULE_SUPPORT_ACCOUNT &&
                in_array($account_info['type'], array(ACCOUNT_TYPE_APP_NORMAL, ACCOUNT_TYPE_APP_AUTH))) {
                continue;
            }
            if ($module_info[MODULE_SUPPORT_SYSTEMWELCOME_NAME] == MODULE_SUPPORT_SYSTEMWELCOME &&
                $module_info[MODULE_SUPPORT_ACCOUNT_NAME] != MODULE_SUPPORT_ACCOUNT &&
                $module_info[MODULE_SUPPORT_WEBAPP_NAME] != MODULE_SUPPORT_WEBAPP &&
                $module_info[MODULE_SUPPORT_PHONEAPP_NAME] != MODULE_SUPPORT_PHONEAPP &&
                $module_info[MODULE_SUPPORT_ALIAPP_NAME] != MODULE_SUPPORT_ALIAPP &&
                $module_info[MODULE_SUPPORT_BAIDUAPP_NAME] != MODULE_SUPPORT_BAIDUAPP &&
                $module_info[MODULE_SUPPORT_WXAPP_NAME] != MODULE_SUPPORT_WXAPP) {
                continue;
            }
            if (!empty($module_info)) {
                $module_list[$name] = $module_info;
            }
        }
    }
    return $module_list;
}

/**
 * 获取当前公号下所有安装模块及模块信息
 * @return array 模块列表
 */
function uni_modules() {
    return pdo_getall('modules');
}

/**
 * 保存公众号的配置数据
 * @param string $name
 * @param mixed $value
 * @return boolean
 */
function uni_setting_save($name, $value) {
    global $_W;
    $uniacid = !empty($_W['uniacid']) ? $_W['uniacid'] : $_W['account']['uniacid'];
    if (empty($name)) {
        return false;
    }
    if (is_array($value)) {
        $value = serialize($value);
    }
    $unisetting = pdo_get('uni_settings', array('uniacid' => $uniacid), array('uniacid'));
    if (!empty($unisetting)) {
        pdo_update('uni_settings', array($name => $value), array('uniacid' => $uniacid));
    } else {
        pdo_insert('uni_settings', array($name => $value, 'uniacid' => $uniacid));
    }
    cache_delete(cache_system_key('uniaccount', array('uniacid' => $uniacid)));
    return true;
}

/**
 * 获取公众号的配置项
 * @param string | array $name
 * @param int $uniacid 统一公号id, uniacid
 * @return array 设置项
 */
function uni_setting_load($name = '', $uniacid = 0) {
    global $_W;
    $uniacid = empty($uniacid) ? $_W['uniacid'] : $uniacid;
    $cachekey = cache_system_key('unisetting', array('uniacid' => $uniacid));
    $unisetting = cache_load($cachekey);
    if (empty($unisetting) || ($name == 'remote' && empty($unisetting['remote']))) {
        $unisetting = pdo_get('uni_settings', array('uniacid' => $uniacid));
        if (!empty($unisetting)) {
            $serialize = array('site_info', 'stat', 'oauth', 'passport', 'notify',
                'creditnames', 'default_message', 'creditbehaviors', 'payment',
                'recharge', 'tplnotice', 'mcplugin', 'statistics', 'bind_domain', 'remote');
            foreach ($unisetting as $key => &$row) {
                if (in_array($key, $serialize) && !empty($row)) {
                    $row = (array)iunserializer($row);
                }
            }
        } else {
            $unisetting = array();
        }
        cache_write($cachekey, $unisetting);
    }
    if (empty($unisetting)) {
        return array();
    }
    if (empty($name)) {
        return $unisetting;
    }
    if (!is_array($name)) {
        $name = array($name);
    }
    return array_elements($name, $unisetting);
}

/**
 * 获取指定子公号信息
 * @param int $acid 子公号acid
 * @return array
 */
function account_fetch($acid) {
    $account_info = pdo_get('account', array('acid' => $acid));
    if (empty($account_info)) {
        return error(-1, '公众号不存在');
    }
    return uni_fetch($account_info['uniacid']);
}

/**
 * 获取全局oauth信息
 * @return string
 */
function uni_account_global_oauth() {
    load()->model('setting');
    $oauth = setting_load('global_oauth');
    $oauth = !empty($oauth['global_oauth']) ? $oauth['global_oauth'] : array();
    if (!empty($oauth['oauth']['account'])) {
        $account_exist = uni_fetch($oauth['oauth']['account']);
        if (empty($account_exist) || is_error($account_exist)) {
            $oauth['oauth']['account'] = 0;
        }
    }
    return $oauth;
}

/**
 * 获取公众号的有效的 oauth 域名
 * @param $unisetting
 */
function uni_account_oauth_host() {
    global $_W;
    $oauth_url = $_W['siteroot'];
    $unisetting = uni_setting_load();
    if (!empty($unisetting['bind_domain']) && !empty($unisetting['bind_domain']['domain'])) {
        $oauth_url = $unisetting['bind_domain']['domain'] . '/';
    } else {
        if (ACCOUNT_TYPE_OFFCIAL_NORMAL == $_W['account']['type']) {
            if (!empty($unisetting['oauth']['host'])) {
                $oauth_url = $unisetting['oauth']['host'] . '/';
            } else {
                $global_unisetting = uni_account_global_oauth();
                $oauth_url = !empty($global_unisetting['oauth']['host']) ? $global_unisetting['oauth']['host'] . '/' : $oauth_url;
            }
        }
    }
    return $oauth_url;
}

/**
 * @param $rid 规则ID
 * @param $relate_table_name 关联的回复表
 * @return bool
 */
function uni_delete_rule($rid, $relate_table_name) {
    global $_W;
    $rid = intval($rid);
    if (empty($rid)) {
        return false;
    }
    $allowed_table_names = array('news_reply', 'cover_reply');
    if (!in_array($relate_table_name, $allowed_table_names)) {
        return false;
    }
    $rule_result = pdo_delete('rule', array('id' => $rid, 'uniacid' => $_W['uniacid']));
    $rule_keyword_result = pdo_delete('rule_keyword', array('rid' => $rid, 'uniacid' => $_W['uniacid']));
    if ($rule_result && $rule_keyword_result) {
        $result = pdo_delete($relate_table_name, array('rid' => $rid));
    }
    return $result ? true : false;
}

function uni_init_accounts() {
    global $_W;
    load()->library('sdk-module');
    try {
        $api = new \W7\Sdk\Module\Api($_W['setting']['server_setting']['app_id'], $_W['setting']['server_setting']['app_secret'], 0, V3_API_DOMAIN);
        $accounts = $api->getAccountList()->toArray();
        $uni_accounts = pdo_getall('uni_account', [], [], 'type');
        pdo_delete('account');
        foreach ($accounts as $account) {
            if (!empty($uni_accounts[$account['type']])) {
                $uniacid = $uni_accounts[$account['type']]['uniacid'];
            } else {
                pdo_insert('uni_account', [
                    'type' => $account['type'],
                    'isconnect' => 1,
                    'createtime' => TIMESTAMP
                ]);
                $uniacid = pdo_insertid();
            }
            $data = [
                'uniacid' => $uniacid,
                'name' => $account['name'],
                'logo' => $account['logo_url'],
                'type' => $account['type'],
                'level' => intval($account['account_type']), //1订阅号;2服务号;3认证订阅号;4认证服务号
                'access_type' => intval($account['access_type']), //1普通接入;2授权接入
                'app_id' => $account['app_id'],
                'token' => $account['token'],
                'aes_key' => $account['aes_key'],
            ];
            pdo_insert('account', $data);
        }
    } catch (Exception $e) {
        return error(-1, $e->getMessage());
    }
    return true;
}
