<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$
 */
defined('IN_IA') or exit('Access Denied');

function module_support_type() {
    //根据模块类型分类
    $module_support_type = array(
        'wxapp_support' => array(
            'type' => WXAPP_TYPE_SIGN,
            'type_num' => ACCOUNT_TYPE_APP_NORMAL,
            'type_name' => '微信小程序',
            'support' => MODULE_SUPPORT_WXAPP,
            'not_support' => MODULE_NONSUPPORT_WXAPP,
        ),
        'account_support' => array(
            'type' => ACCOUNT_TYPE_SIGN,
            'type_num' => ACCOUNT_TYPE_OFFCIAL_NORMAL,
            'type_name' => '公众号',
            'support' => MODULE_SUPPORT_ACCOUNT,
            'not_support' => MODULE_NONSUPPORT_ACCOUNT,
        ),
        'welcome_support' => array(
            'type' => WELCOMESYSTEM_TYPE_SIGN,
            'type_num' => ACCOUNT_TYPE_WELCOMESYSTEM_NORMAL,
            'type_name' => '系统首页',
            'support' => MODULE_SUPPORT_SYSTEMWELCOME,
            'not_support' => MODULE_NONSUPPORT_SYSTEMWELCOME,
        ),
        'webapp_support' => array(
            'type' => WEBAPP_TYPE_SIGN,
            'type_num' => ACCOUNT_TYPE_WEBAPP_NORMAL,
            'type_name' => 'PC',
            'support' => MODULE_SUPPORT_WEBAPP,
            'not_support' => MODULE_NOSUPPORT_WEBAPP,
        ),
        'phoneapp_support' => array(
            'type' => PHONEAPP_TYPE_SIGN,
            'type_num' => ACCOUNT_TYPE_PHONEAPP_NORMAL,
            'type_name' => 'APP',
            'support' => MODULE_SUPPORT_PHONEAPP,
            'not_support' => MODULE_NOSUPPORT_PHONEAPP,
        ),
        'aliapp_support' => array(
            'type' => ALIAPP_TYPE_SIGN,
            'type_num' => ACCOUNT_TYPE_ALIAPP_NORMAL,
            'type_name' => '支付宝小程序',
            'support' => MODULE_SUPPORT_ALIAPP,
            'not_support' => MODULE_NOSUPPORT_ALIAPP,
        ),
        'baiduapp_support' => array(
            'type' => BAIDUAPP_TYPE_SIGN,
            'type_num' => ACCOUNT_TYPE_BAIDUAPP_NORMAL,
            'type_name' => '百度小程序',
            'support' => MODULE_SUPPORT_BAIDUAPP,
            'not_support' => MODULE_NOSUPPORT_BAIDUAPP,
        ),
        'toutiaoapp_support' => array(
            'type' => TOUTIAOAPP_TYPE_SIGN,
            'type_num' => ACCOUNT_TYPE_TOUTIAOAPP_NORMAL,
            'type_name' => '字节跳动小程序',
            'support' => MODULE_SUPPORT_TOUTIAOAPP,
            'not_support' => MODULE_NOSUPPORT_TOUTIAOAPP,
        )
    );
    return $module_support_type;
}

/**
 * 获取指定模块的所有入口地址
 *
 * @param string $name 模块名称
 * @param string|array $types 入口类型
 * @param number $rid 规则编号
 * @param string $args 附加参数
 * @return array
 */
function module_entries($name, $types = array(), $rid = 0, $args = null) {
    load()->func('communication');

    global $_W;
    
    $ts = array('rule', 'cover', 'menu', 'home', 'profile', 'shortcut', 'function', 'mine');
    
    if (empty($types)) {
        $types = $ts;
    } else {
        $types = array_intersect($types, $ts);
    }
    $bindings = pdo_getall('modules_bindings', array('module' => $name, 'entry' => $types), array(), '', 'displayorder DESC, multilevel DESC, eid ASC');
    $entries = array();
    $cache_key = cache_system_key('module_entry_call', array('module_name' => $name, 'uniacid' => $_W['uniacid']));
    $entry_call = cache_load($cache_key);
    if (empty($entry_call)) {
        $entry_call = array();
    }
    foreach ($bindings as $bind) {
        if (!empty($bind['call'])) {
            if (empty($entry_call[$bind['entry']])) {
                $call_url = url('utility/bindcall', array('modulename' => $bind['module'], 'callname' => $bind['call'], 'args' => $args, 'uniacid' => $_W['uniacid']));
                $response = ihttp_request($call_url);
                if (is_error($response) || $response['code'] != 200) {
                    $response = ihttp_request($_W['siteroot'] . 'web/' . $call_url); //127.0.0.1 get 不到数据时,尝试使用域名get
                    if (is_error($response) || $response['code'] != 200) {
                        continue;
                    }
                }
                $response = json_decode($response['content'], true);
                $ret = empty($response['message']['message']) ? '' : $response['message']['message'];
                if (is_array($ret)) {
                    foreach ($ret as $i => $et) {
                        if (empty($et['url'])) {
                            continue;
                        }
                        $urlinfo = url_params($et['url']);
                        $et['do'] = empty($et['do']) ? $urlinfo['do'] : $et['do'];
                        $et['url'] = $et['url'] . '&__title=' . urlencode($et['title']);
                        $entry_call[$bind['entry']][] = array('eid' => 'user_' . $i, 'title' => $et['title'], 'do' => $et['do'], 'url' => $et['url'], 'from' => 'call', 'icon' => $et['icon'] ?? '', 'displayorder' => $et['displayorder'] ?? 0);
                    }
                }
                cache_write($cache_key, $entry_call, 300);
            }
            $entries[$bind['entry']] = empty($entry_call[$bind['entry']]) ? array() : $entry_call[$bind['entry']];
        } else {
            if (in_array($bind['entry'], array('cover', 'home', 'profile', 'shortcut'))) {
                $url = murl('entry', array('eid' => $bind['eid']));
            }
            if (in_array($bind['entry'], array('menu', 'system_welcome'))) {
                $url = wurl("site/entry", array('eid' => $bind['eid']));
            }
            if ($bind['entry'] == 'mine') {
                $url = $bind['url'];
            }
            if ($bind['entry'] == 'rule') {
                $par = array('eid' => $bind['eid']);
                if (!empty($rid)) {
                    $par['id'] = $rid;
                }
                $url = wurl("site/entry", $par);
            }

            if (empty($bind['icon'])) {
                $bind['icon'] = 'wi wi-appsetting';
            }
            if (!defined('SYSTEM_WELCOME_MODULE') && $bind['entry'] == 'system_welcome') {
                continue;
            }
            $entries[$bind['entry']][] = array(
                'eid' => $bind['eid'],
                'title' => $bind['title'],
                'do' => $bind['do'],
                'url' => !$bind['multilevel'] ? $url : '',
                'from' => 'define',
                'icon' => $bind['icon'],
                'displayorder' => $bind['displayorder'],
                'direct' => $bind['direct'],
                'multilevel' => $bind['multilevel'],
                'parent' => $bind['parent'],
            );
        }
    }
    return $entries;
}

function module_entry($eid) {
    $sql = "SELECT * FROM " . tablename('modules_bindings') . " WHERE `eid`=:eid";
    $pars = array();
    $pars[':eid'] = $eid;
    $entry = pdo_fetch($sql, $pars);
    if (empty($entry)) {
        return error(1, '模块菜单不存在');
    }
    $module = module_fetch($entry['module']);
    if (empty($module)) {
        return error(2, '模块不存在');
    }
    $querystring = array(
        'do' => $entry['do'],
        'm' => $entry['module'],
    );
    if (!empty($entry['state'])) {
        $querystring['state'] = $entry['state'];
    }

    $entry['url'] = murl('entry', $querystring);
    $entry['url_show'] = murl('entry', $querystring, true, true);
    return $entry;
}

/**
 * 获取指定模块及模块信息
 * @param string $name 模块名称
 * @return array 模块信息
 */
function module_fetch($name) {
    $cachekey = cache_system_key('module_info', array('module_name' => $name));
    $module = cache_load($cachekey);
    if (empty($module)) {
        $module_info = table('modules')->getByName($name);
        if (empty($module_info)) {
            return array();
        }
        $module_info['isdisplay'] = 1;
        $module_info['logo'] = tomedia($module_info['logo']);
        $modules_plugin = table('modules_plugin')->getAllByNameOrMainModule($module_info['name']);
        $main_module = array_column($modules_plugin, 'main_module');
        if (in_array($module_info['name'], $main_module)) {
            $module_info['plugin_list'] = array_column($modules_plugin, 'name');
        } else {
            $module_info['main_module'] = current($main_module);
            if (!empty($module_info['main_module'])) {
                $main_module_info = module_fetch($module_info['main_module']);
                if (empty($main_module_info)) {
                    $main_module_info = pdo_get('modules_cloud', array('name' => $module_info['main_module']));
                }
                $module_info['main_module_logo'] = $main_module_info['logo'];
                $module_info['main_module_title'] = $main_module_info['title'];
            }
        }
        $module = $module_info;
        cache_write($cachekey, $module_info);
    }
    return $module;
}

/**
 *  获取指定模块在当前公众号安装的插件
 * @param string $module_name 模块标识
 * @param array() $plugin_list 插件列表
 */
function module_get_plugin_list($module_name) {
    $module_info = module_fetch($module_name);
    if (!empty($module_info['plugin_list']) && is_array($module_info['plugin_list'])) {
        $plugin_list = array();
        foreach ($module_info['plugin_list'] as $plugin) {
            $plugin_info = module_fetch($plugin);
            if (!empty($plugin_info)) {
                $plugin_list[$plugin] = $plugin_info;
            }
        }
        return $plugin_list;
    } else {
        return array();
    }
}

function module_plugin_list($module_name = '') {
    global $_W;
	load()->model('cloud');
	if (empty($module_name) || empty($_W['setting']['copyright']['cloud_status'])) {
		return array();
	}
	$cachekey = cache_system_key('plugins', array('module_name' => 'main_module'));
	$plugins = cache_load($cachekey);
	if (empty($plugins)) {
		$cloud_plugins = cloud_m_plugins($module_name);
		if (is_error($cloud_plugins)) {
			return $cloud_plugins;
		}
		$plugins = array();
		foreach ($cloud_plugins as $id => $plugin) {
			if (!is_array($plugin) || empty($plugin['name'])) {
				continue;
			}
			$plugin_exist = module_fetch($plugin['name']);
			if (empty($plugin_exist)) {
				$supports = $plugin['support_types'];
				foreach ($supports as &$support) {
					if ('app' == $support) {
						$support = 'wx-circle';
					} elseif ('system_welcome' == $support) {
						$support = 'welcome';
					} elseif ('android' == $support || 'ios' == $support) {
						$support = 'phoneapp';
					}
				}
				$plugin_info = array(
					'cloud_id' => $id,
					'name' => $plugin['name'],
					'title' => $plugin['title'],
					'version' => $plugin['version_max'],
					'description' => $plugin['design_description'],
					'logo' => $plugin['logo'],
					'url' => $plugin['url'],
					'is_bought' => $plugin['is_bought'],
					'is_install' => false,
					'service_expiretime' => $plugin['service_expiretime'],
					'support_types' => $supports
				);
				array_push($plugins, $plugin_info);
			} else {
				$plugin_info = array(
					'cloud_id' => $id,
					'name' => $plugin['name'],
					'title' => $plugin_exist['title'],
					'version' => $plugin_exist['version'],
					'description' => $plugin_exist['description'],
					'logo' => $plugin_exist['logo'],
					'url' => $plugin['url'],
					'is_bought' => $plugin['is_bought'],
					'is_install' => true,
					'service_expiretime' => $plugin['service_expiretime'],
					'support_types' => array()
				);
				array_unshift($plugins, $plugin_info);
			}
		}
		if (empty($plugins)) {
			return $plugins;
		}
		cache_write($cachekey, $plugins);
	}

	return $plugins;
}
